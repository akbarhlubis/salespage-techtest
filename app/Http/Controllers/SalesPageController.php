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

            $data = $this->normalizeData($salesPage->generated_data);

            if (in_array($section, ['benefits', 'cta'])) {
                $decoded = json_decode($newContent, true);
                $data[$section] = $decoded ?? $data[$section];
            } else {
                $data[$section] = $newContent;
            }

            $html = $this->renderSalesPageHtml($data, $salesPage->toArray());
            $salesPage->update(['generated_data' => $data, 'generated_html' => $html]);

            // Return HTML sekalian supaya iframe bisa update tanpa reload
            return response()->json([
                'success' => true,
                'content' => $newContent,
                'html'    => $html,
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

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
        $data = $this->normalizeData($data);

        $style = $product['style'] ?? 'modern';
        $colors = match($style) {
            'minimal' => ['primary' => '#0a0a0a', 'accent' => '#0057ff', 'bg' => '#fafafa', 'card' => '#ffffff', 'muted' => '#6b7280'],
            'bold'    => ['primary' => '#ffffff', 'accent' => '#ff2d2d', 'bg' => '#080808', 'card' => '#111111', 'muted' => '#888888'],
            default   => ['primary' => '#0f172a', 'accent' => '#6c63ff', 'bg' => '#f8f7ff', 'card' => '#ffffff', 'muted' => '#64748b'],
        };

        $headline      = is_string($data['headline']             ?? null) ? htmlspecialchars($data['headline'])             : '';
        $sub_headline  = is_string($data['sub_headline']         ?? null) ? htmlspecialchars($data['sub_headline'])         : '';
        $product_desc  = is_string($data['product_description']  ?? null) ? htmlspecialchars($data['product_description'])  : '';
        $seo_desc      = is_string($data['seo_meta_description'] ?? null) ? htmlspecialchars($data['seo_meta_description']) : '';
        $product_name  = htmlspecialchars($product['product_name'] ?? '');

        $benefits = collect($data['benefits'])->map(fn($b) =>
            is_array($b)
                ? "<div class='benefit-card'>
                    <div class='benefit-icon'>" . htmlspecialchars($b['icon'] ?? '⭐') . "</div>
                    <div class='benefit-title'>" . htmlspecialchars($b['title'] ?? '') . "</div>
                    <div class='benefit-desc'>" . htmlspecialchars($b['description'] ?? '') . "</div>
                   </div>"
                : "<div class='benefit-card'><div class='benefit-desc'>" . htmlspecialchars($b) . "</div></div>"
        )->implode('');

        $features = collect($data['features'])->map(fn($f) =>
            is_array($f)
                ? "<div class='feature-item'>
                    <span class='feature-check'>✓</span>
                    <div><strong>" . htmlspecialchars($f['name'] ?? '') . "</strong> — " . htmlspecialchars($f['detail'] ?? '') . "</div>
                   </div>"
                : "<div class='feature-item'><span class='feature-check'>✓</span><div>" . htmlspecialchars($f) . "</div></div>"
        )->implode('');

        $testimonials = collect($data['social_proof'])->map(fn($t) =>
            is_array($t)
                ? "<div class='testimonial'>
                    <div class='stars'>★★★★★</div>
                    <div class='testimonial-quote'>\"" . htmlspecialchars($t['quote'] ?? '') . "\"</div>
                    <div class='testimonial-author'><strong>" . htmlspecialchars($t['name'] ?? '') . "</strong> · " . htmlspecialchars($t['role'] ?? '') . "</div>
                   </div>"
                : "<div class='testimonial'><div class='stars'>★★★★★</div><div class='testimonial-quote'>" . htmlspecialchars($t) . "</div></div>"
        )->implode('');

        $pricing_price = htmlspecialchars($data['pricing']['price'] ?? '');
        $pricing_note  = htmlspecialchars($data['pricing']['note']  ?? '');
        $includes = collect($data['pricing']['includes'] ?? [])->map(fn($i) =>
            "<li><span>✓</span> " . htmlspecialchars($i) . "</li>"
        )->implode('');

        $cta_primary   = is_array($data['cta']) ? htmlspecialchars($data['cta']['primary_text']   ?? '') : htmlspecialchars((string)($data['cta'] ?? ''));
        $cta_secondary = is_array($data['cta']) ? htmlspecialchars($data['cta']['secondary_text'] ?? '') : '';

        $font_pair = match($style) {
            'minimal' => ['display' => 'DM Serif Display', 'body' => 'DM Sans'],
            'bold'    => ['display' => 'Bebas Neue', 'body' => 'Inter'],
            default   => ['display' => 'Syne', 'body' => 'DM Sans'],
        };

        $google_fonts = "https://fonts.googleapis.com/css2?family={$font_pair['display']}:wght@400;700&family={$font_pair['body']}:wght@300;400;500;600&display=swap";

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$headline}</title>
<meta name="description" content="{$seo_desc}">
<link href="{$google_fonts}" rel="stylesheet">
<style>
  :root {
    --primary: {$colors['primary']};
    --accent: {$colors['accent']};
    --bg: {$colors['bg']};
    --card: {$colors['card']};
    --muted: {$colors['muted']};
    --font-display: '{$font_pair['display']}', serif;
    --font-body: '{$font_pair['body']}', sans-serif;
    --radius: 20px;
    --shadow: 0 4px 32px rgba(0,0,0,0.08);
  }

  * { margin:0; padding:0; box-sizing:border-box; }

  body {
    font-family: var(--font-body);
    background: var(--bg);
    color: var(--primary);
    line-height: 1.6;
  }

  /* Navbar */
  .navbar {
    position: sticky; top:0; z-index:100;
    background: rgba(255,255,255,0.85);
    backdrop-filter: blur(12px);
    border-bottom: 1px solid rgba(0,0,0,0.06);
    padding: 16px 40px;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .navbar-brand {
    font-family: var(--font-display);
    font-size: 1.2rem;
    color: var(--primary);
    letter-spacing: -0.3px;
  }
  .navbar-cta {
    background: var(--accent);
    color: white;
    padding: 8px 20px;
    border-radius: 50px;
    font-size: 0.85rem;
    font-weight: 600;
    text-decoration: none;
    transition: opacity 0.2s;
  }
  .navbar-cta:hover { opacity: 0.85; }

  /* Hero */
  .hero {
    background: linear-gradient(135deg, var(--primary) 0%, color-mix(in srgb, var(--accent) 60%, var(--primary)) 100%);
    color: white;
    padding: 100px 40px 80px;
    text-align: center;
    position: relative;
    overflow: hidden;
  }
  .hero::before {
    content: '';
    position: absolute; inset: 0;
    background: radial-gradient(ellipse at 30% 50%, rgba(255,255,255,0.08) 0%, transparent 60%),
                radial-gradient(ellipse at 70% 20%, rgba(255,255,255,0.05) 0%, transparent 50%);
  }
  .hero-inner { position: relative; z-index:1; max-width: 800px; margin: 0 auto; }
  .hero-badge {
    display: inline-block;
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.25);
    color: white;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    padding: 6px 16px;
    border-radius: 50px;
    margin-bottom: 24px;
  }
  .hero h1 {
    font-family: var(--font-display);
    font-size: clamp(2.2rem, 5vw, 4rem);
    font-weight: 700;
    line-height: 1.15;
    margin-bottom: 20px;
    letter-spacing: -0.5px;
  }
  .hero p {
    font-size: 1.15rem;
    opacity: 0.85;
    max-width: 560px;
    margin: 0 auto 36px;
  }
  .hero-cta {
    display: inline-block;
    background: white;
    color: var(--accent);
    font-weight: 700;
    font-size: 1rem;
    padding: 16px 40px;
    border-radius: 50px;
    text-decoration: none;
    box-shadow: 0 8px 32px rgba(0,0,0,0.25);
    transition: transform 0.2s, box-shadow 0.2s;
  }
  .hero-cta:hover { transform: translateY(-2px); box-shadow: 0 12px 40px rgba(0,0,0,0.3); }

  /* Stats bar */
  .stats-bar {
    background: var(--card);
    border-bottom: 1px solid rgba(0,0,0,0.06);
    padding: 24px 40px;
    display: flex;
    justify-content: center;
    gap: 60px;
    flex-wrap: wrap;
  }
  .stat-item { text-align: center; }
  .stat-number {
    font-family: var(--font-display);
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--accent);
  }
  .stat-label { font-size: 0.8rem; color: var(--muted); margin-top: 2px; }

  /* Sections */
  .section {
    padding: 80px 40px;
    max-width: 1100px;
    margin: 0 auto;
    opacity: 0;
    transform: translateY(24px);
    transition: opacity 0.6s ease, transform 0.6s ease;
  }
  .section.visible { opacity: 1; transform: translateY(0); }
  .section-label {
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--accent);
    margin-bottom: 12px;
  }
  .section-title {
    font-family: var(--font-display);
    font-size: clamp(1.8rem, 3vw, 2.8rem);
    font-weight: 700;
    margin-bottom: 16px;
    letter-spacing: -0.3px;
  }
  .section-subtitle {
    font-size: 1.05rem;
    color: var(--muted);
    max-width: 560px;
    margin-bottom: 48px;
  }

  /* Description */
  .desc-section {
    background: var(--card);
    padding: 80px 40px;
    text-align: center;
    border-top: 1px solid rgba(0,0,0,0.05);
    border-bottom: 1px solid rgba(0,0,0,0.05);
  }
  .desc-text {
    max-width: 680px;
    margin: 0 auto;
    font-size: 1.1rem;
    line-height: 1.85;
    color: var(--muted);
  }

  /* Benefits */
  .benefits-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
    gap: 20px;
  }
  .benefit-card {
    background: var(--card);
    border-radius: var(--radius);
    padding: 32px 28px;
    box-shadow: var(--shadow);
    border: 1px solid rgba(0,0,0,0.04);
    transition: transform 0.2s, box-shadow 0.2s;
  }
  .benefit-card:hover { transform: translateY(-4px); box-shadow: 0 12px 40px rgba(0,0,0,0.12); }
  .benefit-icon { font-size: 2.2rem; margin-bottom: 14px; }
  .benefit-title { font-size: 1rem; font-weight: 700; margin-bottom: 8px; }
  .benefit-desc { font-size: 0.9rem; color: var(--muted); line-height: 1.55; }

  /* Features */
  .features-wrap {
    background: var(--card);
    border-radius: var(--radius);
    overflow: hidden;
    box-shadow: var(--shadow);
    border: 1px solid rgba(0,0,0,0.04);
  }
  .feature-item {
    display: flex;
    gap: 16px;
    align-items: flex-start;
    padding: 20px 28px;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    transition: background 0.15s;
  }
  .feature-item:last-child { border-bottom: none; }
  .feature-item:hover { background: rgba(0,0,0,0.015); }
  .feature-check {
    width: 24px; height: 24px;
    background: var(--accent);
    color: white;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.7rem;
    font-weight: 700;
    flex-shrink: 0;
    margin-top: 2px;
  }

  /* Testimonials */
  .testimonials-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
  }
  .testimonial {
    background: var(--card);
    border-radius: var(--radius);
    padding: 28px;
    box-shadow: var(--shadow);
    border: 1px solid rgba(0,0,0,0.04);
    position: relative;
  }
  .testimonial::before {
    content: '"';
    font-family: var(--font-display);
    font-size: 5rem;
    color: var(--accent);
    opacity: 0.15;
    position: absolute;
    top: 8px; left: 20px;
    line-height: 1;
  }
  .stars { color: #f59e0b; font-size: 0.85rem; margin-bottom: 12px; letter-spacing: 2px; }
  .testimonial-quote { font-size: 0.95rem; color: var(--muted); line-height: 1.65; margin-bottom: 16px; font-style: italic; }
  .testimonial-author { font-size: 0.85rem; font-weight: 600; }

  /* Pricing */
  .pricing-section {
    background: linear-gradient(135deg, var(--primary), color-mix(in srgb, var(--accent) 50%, var(--primary)));
    padding: 80px 40px;
    text-align: center;
  }
  .pricing-box {
    background: rgba(255,255,255,0.07);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 28px;
    padding: 56px 48px;
    max-width: 520px;
    margin: 0 auto;
    backdrop-filter: blur(10px);
    color: white;
  }
  .pricing-product { font-size: 0.8rem; letter-spacing: 2px; text-transform: uppercase; opacity: 0.7; margin-bottom: 8px; }
  .pricing-price {
    font-family: var(--font-display);
    font-size: 4rem;
    font-weight: 700;
    margin: 16px 0 8px;
    letter-spacing: -1px;
  }
  .pricing-note { opacity: 0.65; font-size: 0.9rem; margin-bottom: 28px; }
  .pricing-includes {
    list-style: none;
    text-align: left;
    max-width: 280px;
    margin: 0 auto 36px;
  }
  .pricing-includes li {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 0;
    font-size: 0.9rem;
    opacity: 0.9;
    border-bottom: 1px solid rgba(255,255,255,0.08);
  }
  .pricing-includes li:last-child { border-bottom: none; }
  .pricing-includes span { color: #4ade80; font-weight: 700; }
  .cta-btn {
    display: inline-block;
    background: white;
    color: var(--accent);
    font-weight: 800;
    font-size: 1rem;
    padding: 18px 52px;
    border-radius: 50px;
    text-decoration: none;
    box-shadow: 0 8px 32px rgba(0,0,0,0.3);
    transition: transform 0.2s, box-shadow 0.2s;
    letter-spacing: 0.3px;
  }
  .cta-btn:hover { transform: translateY(-2px); box-shadow: 0 12px 40px rgba(0,0,0,0.4); }
  .cta-note { margin-top: 14px; opacity: 0.6; font-size: 0.85rem; }

  /* Footer */
  .footer {
    background: var(--primary);
    color: rgba(255,255,255,0.4);
    text-align: center;
    padding: 24px;
    font-size: 0.8rem;
  }

  @media(max-width: 768px) {
    .hero { padding: 70px 20px 60px; }
    .section { padding: 60px 20px; }
    .stats-bar { gap: 30px; padding: 20px; }
    .navbar { padding: 14px 20px; }
    .pricing-box { padding: 40px 24px; }
    .pricing-price { font-size: 3rem; }
  }
</style>
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar">
    <div class="navbar-brand">{$product_name}</div>
    <a href="#pricing" class="navbar-cta">{$cta_primary}</a>
  </nav>

  <!-- Hero -->
  <section class="hero">
    <div class="hero-inner">
      <div class="hero-badge">✦ New Launch</div>
      <h1>{$headline}</h1>
      <p>{$sub_headline}</p>
      <a href="#pricing" class="hero-cta">{$cta_primary} →</a>
    </div>
  </section>

  <!-- Stats Bar -->
  <div class="stats-bar">
    <div class="stat-item">
      <div class="stat-number">500+</div>
      <div class="stat-label">Happy Customers</div>
    </div>
    <div class="stat-item">
      <div class="stat-number">4.9★</div>
      <div class="stat-label">Average Rating</div>
    </div>
    <div class="stat-item">
      <div class="stat-number">24/7</div>
      <div class="stat-label">Support</div>
    </div>
  </div>

  <!-- Description -->
  <div class="desc-section">
    <p class="desc-text">{$product_desc}</p>
  </div>

  <!-- Benefits -->
  <div class="section" data-animate>
    <div class="section-label">Why Choose Us</div>
    <div class="section-title">Built for Results</div>
    <p class="section-subtitle">Everything you need, nothing you don't.</p>
    <div class="benefits-grid">{$benefits}</div>
  </div>

  <!-- Features -->
  <div class="section" data-animate>
    <div class="section-label">What's Included</div>
    <div class="section-title">Every Feature You Need</div>
    <p class="section-subtitle">Packed with tools that actually make a difference.</p>
    <div class="features-wrap">{$features}</div>
  </div>

  <!-- Testimonials -->
  <div class="section" data-animate>
    <div class="section-label">Social Proof</div>
    <div class="section-title">Real People, Real Results</div>
    <p class="section-subtitle">Don't take our word for it.</p>
    <div class="testimonials-grid">{$testimonials}</div>
  </div>

  <!-- Pricing -->
  <section class="pricing-section" id="pricing">
    <div class="pricing-box">
      <div class="pricing-product">{$product_name}</div>
      <div class="pricing-price">{$pricing_price}</div>
      <p class="pricing-note">{$pricing_note}</p>
      <ul class="pricing-includes">{$includes}</ul>
      <a href="#" class="cta-btn">{$cta_primary}</a>
      <p class="cta-note">{$cta_secondary}</p>
    </div>
  </section>

  <footer class="footer">
    © 2025 {$product_name}. All rights reserved.
  </footer>

  <script>
    // Scroll animation
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(e => {
        if (e.isIntersecting) {
          e.target.classList.add('visible');
          observer.unobserve(e.target);
        }
      });
    }, { threshold: 0.1 });

    document.querySelectorAll('[data-animate]').forEach(el => observer.observe(el));
  </script>
</body>
</html>
HTML;
    }
}