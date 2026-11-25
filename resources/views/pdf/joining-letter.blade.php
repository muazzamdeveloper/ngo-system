<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<style>{!! $css !!}</style>
</head>
<body>
<div class="joining-letter-page">
    <header class="jl-header">
        <div class="jl-brand">
            @if ($logoUri)
                <img class="jl-logo" src="{{ $logoUri }}" alt="Logo">
            @endif
            <div class="jl-site-title">{{ $siteTitle }}</div>
            @if ($address !== '')
                <div class="jl-address">{!! nl2br(e($address)) !!}</div>
            @endif
        </div>
        <div class="jl-authority">
            @if ($udyam !== '')
                <div class="jl-udyam">Udyam No: {{ $udyam }}</div>
            @endif
            @if ($signatory !== '')
                <div class="jl-authority-name">{{ $signatory }}</div>
            @endif
            @if ($role !== '')
                <div class="jl-authority-role">{{ $role }}</div>
            @endif
            @if ($phone !== '')
                <div class="jl-authority-phone">{{ $phone }}</div>
            @endif
        </div>
    </header>

    <div class="jl-red-line"></div>

    <div class="jl-date">Date: {{ $letterDate }}</div>

    <h1 class="jl-title">Joining Letter</h1>

    <main class="jl-body">{!! $content !!}</main>

    @if ($signatory !== '' || $role !== '')
        <div class="jl-signature-block">
            <div class="jl-best-regards">Best regards,</div>
            @if ($signatory !== '')
                <p class="jl-signature" style="font-family:'Brush Script MT','Segoe Script','Comic Sans MS',cursive;font-style:italic;font-size:34px;line-height:1;margin:3px 0 2px 0;">{{ $signatory }}</p>
            @endif
            @if ($role !== '')
                <div class="jl-signature-role">{{ $role }}</div>
            @endif
        </div>
    @endif

    <footer class="jl-footer">
        <div class="jl-footer-inner">
            <div class="jl-footer-phone">@if ($iconPhone)<img class="jl-icon" src="{{ $iconPhone }}" alt="">@endif{{ $phone !== '' ? $phone : '-' }}</div>
            <div class="jl-footer-email">@if ($iconMail)<img class="jl-icon" src="{{ $iconMail }}" alt="">@endif{{ $email !== '' ? $email : '-' }}</div>
        </div>
    </footer>
</div>
</body>
</html>
