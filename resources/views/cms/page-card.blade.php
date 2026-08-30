{{--
  Shared CMS page card/badge shell — used by website + API at RENDER time.
  Inline styles so mobile WebViews get the same visual without site CSS.
--}}
@php
    /** @var string $title */
    /** @var string $content */
    $absoluteContent = preg_replace_callback(
        '/\bsrc=(["\'])(\/[^"\']*)\1/i',
        static fn (array $matches): string => 'src='.$matches[1].url($matches[2]).$matches[1],
        $content,
    ) ?? $content;
@endphp
<div class="cms-page-card" data-testid="cms-page-card" style="position:relative;background:#ffffff;border-radius:33px;padding:48px 20px 20px;margin:40px 0;line-height:2;font-size:1.25rem;">
    <span
        class="cms-page-title-badge"
        data-testid="cms-page-title-badge"
        style="position:absolute;top:0;left:50%;transform:translate(-50%,-50%);background-color:#00686D;color:#ffffff;padding:20px 60px;border-radius:10px;text-align:center;width:max-content;max-width:90%;box-sizing:border-box;"
    >{{ $title }}</span>
    <div class="cms-page-content" data-testid="cms-page-content">
        {!! $absoluteContent !!}
    </div>
</div>
