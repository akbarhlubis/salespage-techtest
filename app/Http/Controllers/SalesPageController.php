<?php
// File: app/Http/Controllers/SalesPageController.php

namespace App\Http\Controllers;

use App\Models\SalesPage;
use App\Services\ClaudeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalesPageController extends Controller
{
    public function __construct(private ClaudeService $claude)
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $pages = Auth::user()->salesPages()
            ->latest()
            ->paginate(10);

        return view('sales-pages.index', compact('pages'));
    }

    public function create()
    {
        return view('sales-pages.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_name'          => 'required|string|max:255',
            'description'           => 'required|string|min:20',
            'features'              => 'required|string',
            'target_audience'       => 'required|string|max:255',
            'price'                 => 'required|string|max:100',
            'unique_selling_points' => 'nullable|string',
            'style'                 => 'nullable|in:modern,minimal,bold',
        ]);

        try {
            $data = $this->claude->generateSalesPage($validated);
            $html = $this->renderSalesPageHtml($data, $validated);

            $page = SalesPage::create([
                'user_id'               => Auth::id(),
                'product_name'          => $validated['product_name'],
                'description'           => $validated['description'],
                'features'              => $validated['features'],
                'target_audience'       => $validated['target_audience'],
                'price'                 => $validated['price'],
                'unique_selling_points' => $validated['unique_selling_points'] ?? null,
                'generated_html'        => $html,
                'generated_data'        => $data,
                'style'                 => $validated['style'] ?? 'modern',
            ]);

            return redirect()->route('sales-pages.show', $page)
                ->with('success', 'Sales page generated successfully!');

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['ai' => $e->getMessage()]);
        }
    }

    public function show(SalesPage $salesPage)
    {
        $this->authorize('view', $salesPage);
        return view('sales-pages.show', compact('salesPage'));
    }

    public function destroy(SalesPage $salesPage)
    {
        $this->authorize('delete', $salesPage);
        $salesPage->delete();

        return redirect()->route('sales-pages.index')
            ->with('success', 'Sales page deleted.');
    }

    public function exportHtml(SalesPage $salesPage)
    {
        $this->authorize('view', $salesPage);

        $filename = \Str::slug($salesPage->product_name) . '-sales-page.html';

        return response($salesPage->generated_html)
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    public function regenerateSection(Request $request, SalesPage $salesPage)
    {
        $this->authorize('view', $salesPage);

        $section = $request->validate([
            'section' => 'required|in:headline,sub_headline,description,benefits,cta'
        ])['section'];

        try {
            $newContent = $this->claude->regenerateSection(
                $salesPage->only(['product_name', 'description', 'target_audience', 'price']),
                $section,
                $salesPage->generated_data
            );

            // Ambil data dan pastikan semua nested key tetap array
            $data = $salesPage->generated_data;
            $data = $this->normalizeData($data);

            // Update section yang diregenerasi
            if (in_array($section, ['benefits', 'cta'])) {
                $decoded = json_decode($newContent, true);
                $data[$section] = $decoded ?? $data[$section]; // fallback ke data lama kalau JSON invalid
            } else {
                $data[$section] = $newContent;
            }

            $html = $this->renderSalesPageHtml($data, $salesPage->toArray());
            $salesPage->update(['generated_data' => $data, 'generated_html' => $html]);

            return response()->json(['success' => true, 'content' => $newContent]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Pastikan semua key yang harusnya array tetap array.
     * Mencegah "Cannot access offset of type string on string"
     * ketika data dari DB di-cast ulang dengan tipe yang salah.
     */
    private function normalizeData(array $data): array
    {
        $data['benefits']     = is_array($data['benefits']     ?? null) ? $data['benefits']     : [];
        $data['features']     = is_array($data['features']     ?? null) ? $data['features']     : [];
        $data['social_proof'] = is_array($data['social_proof'] ?? null) ? $data['social_proof'] : [];
        $data['pricing']      = is_array($data['pricing']      ?? null) ? $data['pricing']      : [];
        $data['cta']          = is_array($data['cta']          ?? null) ? $data['cta']          : [];

        return $data;
    }

    private function renderSalesPageHtml(array $data, array $product): string
    {
        // Normalize dulu sebelum render
        $data = $this->normalizeData($data);

        $style = $product['style'] ?? 'modern';
        $colors = match($style) {
            'minimal' => ['primary' => '#111111', 'accent' => '#0066ff', 'bg' => '#ffffff'],
            'bold'    => ['primary' => '#1a0533', 'accent' => '#ff3d00', 'bg' => '#0f0f0f'],
            default   => ['primary' => '#0f172a', 'accent' => '#6366f1', 'bg' => '#f8fafc'],
        };

        // String fields — guard semua akses langsung ke $data
        $headline      = is_string($data['headline']            ?? null) ? $data['headline']            : '';
        $sub_headline  = is_string($data['sub_headline']        ?? null) ? $data['sub_headline']        : '';
        $product_desc  = is_string($data['product_description'] ?? null) ? $data['product_description'] : '';
        $seo_desc      = is_string($data['seo_meta_description']?? null) ? $data['seo_meta_description']: '';

        // Benefits
        $benefits = collect($data['benefits'])->map(fn($b) =>
            is_array($b)
                ? "<div class='benefit-card'>
                    <div class='benefit-icon'>{$b['icon']}</div>
                    <div class='benefit-title'>{$b['title']}</div>
                    <div class='benefit-desc'>{$b['description']}</div>
                   </div>"
                : "<div class='benefit-card'><div class='benefit-desc'>{$b}</div></div>"
        )->implode('');

        // Features
        $features = collect($data['features'])->map(fn($f) =>
            is_array($f)
                ? "<div class='feature-item'>
                    <span class='feature-check'>✓</span>
                    <div><strong>{$f['name']}</strong> — {$f['detail']}</div>
                   </div>"
                : "<div class='feature-item'><span class='feature-check'>✓</span><div>{$f}</div></div>"
        )->implode('');

        // Testimonials
        $testimonials = collect($data['social_proof'])->map(fn($t) =>
            is_array($t)
                ? "<div class='testimonial'>
                    <div class='testimonial-quote'>\"{$t['quote']}\"</div>
                    <div class='testimonial-author'><strong>{$t['name']}</strong> · {$t['role']}</div>
                   </div>"
                : "<div class='testimonial'><div class='testimonial-quote'>{$t}</div></div>"
        )->implode('');

        // Pricing
        $pricing_price = $data['pricing']['price'] ?? '';
        $pricing_note  = $data['pricing']['note']  ?? '';
        $includes = collect($data['pricing']['includes'] ?? [])->map(fn($i) =>
            "<li>✓ {$i}</li>"
        )->implode('');

        // CTA
        $cta_primary   = is_array($data['cta']) ? ($data['cta']['primary_text']   ?? '') : (string)($data['cta'] ?? '');
        $cta_secondary = is_array($data['cta']) ? ($data['cta']['secondary_text'] ?? '') : '';

        // Product name
        $product_name = $product['product_name'] ?? '';

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$headline}</title>
<meta name="description" content="{$seo_desc}">
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: 'Segoe UI', system-ui, sans-serif; background:{$colors['bg']}; color:{$colors['primary']}; }
  .hero { background: linear-gradient(135deg, {$colors['primary']}, {$colors['accent']}); color:white; padding:80px 20px; text-align:center; }
  .hero h1 { font-size:clamp(2rem,5vw,3.5rem); font-weight:800; line-height:1.2; max-width:800px; margin:0 auto 20px; }
  .hero p { font-size:1.2rem; opacity:0.9; max-width:600px; margin:0 auto; }
  .section { padding:60px 20px; max-width:1100px; margin:0 auto; }
  .section-title { text-align:center; font-size:2rem; font-weight:700; margin-bottom:40px; }
  .benefits-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:24px; }
  .benefit-card { background:white; border-radius:16px; padding:28px; box-shadow:0 4px 20px rgba(0,0,0,0.06); text-align:center; }
  .benefit-icon { font-size:2.5rem; margin-bottom:12px; }
  .benefit-title { font-size:1.1rem; font-weight:700; margin-bottom:8px; }
  .benefit-desc { color:#64748b; font-size:0.95rem; line-height:1.5; }
  .features-section { background:white; border-radius:20px; padding:40px; margin:20px 0; box-shadow:0 4px 20px rgba(0,0,0,0.06); }
  .feature-item { display:flex; gap:16px; align-items:flex-start; padding:12px 0; border-bottom:1px solid #f1f5f9; }
  .feature-item:last-child { border-bottom:none; }
  .feature-check { color:{$colors['accent']}; font-weight:700; font-size:1.2rem; flex-shrink:0; margin-top:2px; }
  .testimonials { display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:20px; }
  .testimonial { background:white; border-radius:16px; padding:28px; box-shadow:0 4px 20px rgba(0,0,0,0.06); border-left:4px solid {$colors['accent']}; }
  .testimonial-quote { font-style:italic; color:#334155; line-height:1.6; margin-bottom:16px; }
  .testimonial-author { font-size:0.9rem; color:#64748b; }
  .pricing-box { background: linear-gradient(135deg, {$colors['primary']}, {$colors['accent']}); color:white; border-radius:24px; padding:48px; text-align:center; max-width:500px; margin:0 auto; }
  .pricing-price { font-size:3rem; font-weight:800; margin:20px 0; }
  .pricing-includes { list-style:none; text-align:left; max-width:280px; margin:20px auto; }
  .pricing-includes li { padding:6px 0; opacity:0.9; }
  .cta-btn { display:inline-block; background:white; color:{$colors['accent']}; font-size:1.2rem; font-weight:800; padding:18px 48px; border-radius:50px; cursor:pointer; margin-top:24px; text-decoration:none; box-shadow:0 8px 30px rgba(0,0,0,0.2); }
  .cta-note { opacity:0.8; margin-top:12px; font-size:0.9rem; }
  .product-desc-section { background:#f1f5f9; padding:60px 20px; text-align:center; }
  .product-desc-text { max-width:700px; margin:0 auto; font-size:1.1rem; line-height:1.8; color:#334155; }
  @media(max-width:600px) { .hero { padding:50px 16px; } .section { padding:40px 16px; } .pricing-box { padding:32px 20px; } }
</style>
</head>
<body>
  <div class="hero">
    <h1>{$headline}</h1>
    <p>{$sub_headline}</p>
  </div>

  <div class="product-desc-section">
    <div class="product-desc-text">{$product_desc}</div>
  </div>

  <div class="section">
    <h2 class="section-title">Why Choose Us</h2>
    <div class="benefits-grid">{$benefits}</div>
  </div>

  <div class="section">
    <h2 class="section-title">What's Inside</h2>
    <div class="features-section">{$features}</div>
  </div>

  <div class="section">
    <h2 class="section-title">What Our Customers Say</h2>
    <div class="testimonials">{$testimonials}</div>
  </div>

  <div class="section">
    <div class="pricing-box">
      <h2 style="font-size:1.5rem">{$product_name}</h2>
      <div class="pricing-price">{$pricing_price}</div>
      <p style="opacity:0.8">{$pricing_note}</p>
      <ul class="pricing-includes">{$includes}</ul>
      <a href="#" class="cta-btn">{$cta_primary}</a>
      <p class="cta-note">{$cta_secondary}</p>
    </div>
  </div>
</body>
</html>
HTML;
    }
}