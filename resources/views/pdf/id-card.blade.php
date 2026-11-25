<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<style>{!! $css !!}</style>
</head>
<body>

{{-- ===== FRONT (page 1) ===== --}}
<div class="pdf-page front-page">
  <main id="container">

    <div class="edge edge-t"></div>
    <div class="edge edge-l"></div>
    <div class="edge edge-r"></div>

    <div id="header">
      @if ($logoUri)
        <img class="logo" src="{{ $logoUri }}" alt="Logo">
      @endif

      <h1>{{ $heading }}</h1>

      @if ($udyam)
        <h3>{{ $udyam }}</h3>
      @endif
    </div>

    <div id="photo-box">
      @if ($photoUri)
        <img class="profile-photo" src="{{ $photoUri }}" alt="Photo">
      @else
        <div class="profile-photo-placeholder"><div class="pp-inner"></div></div>
      @endif

      @if ($stampUri)
        <img class="stamp" src="{{ $stampUri }}" alt="Stamp">
      @endif
    </div>

    <div id="name-box">
      <div class="name-table">
        <div class="name-cell">
          <h4>{{ $name }}</h4>
          @if ($designation !== '')
            <p>{{ $designation }}</p>
          @endif
        </div>
      </div>
    </div>

    <div id="third-con">
      @foreach ($rows as $row)
        <p>
          <span>{{ $row[0] }}</span>
          <span>{{ $row[1] }}</span>
        </p>
      @endforeach
    </div>

    <div id="for-con">
      <p>Expiry: {{ $expiryLabel }}</p>

      @if ($qrUri)
        <div class="card-qr">
          <span class="card-qr-label">Website QR</span>
          <img src="{{ $qrUri }}" alt="QR">
        </div>
      @endif
    </div>

  </main>
</div>

{{-- ===== BACK (page 2) ===== --}}
<div class="pdf-page back-page">
  <main class="card">

    <div class="edge edge-t"></div>
    <div class="edge edge-l"></div>
    <div class="edge edge-r"></div>

    <header class="header">
      @if ($logoUri)
        <img class="logo" src="{{ $logoUri }}" alt="Logo">
      @endif
    </header>

    <div class="title">Terms &amp; Conditions</div>

    <section class="terms">
      <ul>
        @foreach ($terms as $term)
          <li>{{ $term }}</li>
        @endforeach
      </ul>
    </section>

    <section class="contact">
      <div class="contact-row">
        <div class="contact-icon">@if ($iconPin)<img src="{{ $iconPin }}" alt="">@endif</div>
        <div class="contact-text address">{!! nl2br(e($address)) !!}</div>
      </div>

      <div class="contact-row">
        <div class="contact-icon">@if ($iconMail)<img src="{{ $iconMail }}" alt="">@endif</div>
        <div class="contact-text">{{ $email }}</div>
      </div>

      <div class="contact-row">
        <div class="contact-icon">@if ($iconPhone)<img src="{{ $iconPhone }}" alt="">@endif</div>
        <div class="contact-text">{{ $phone }}</div>
      </div>
    </section>

    <footer class="footer">
      @if ($site !== '')
        <div class="website">{{ $site }}</div>
      @endif
    </footer>

  </main>
</div>

</body>
</html>
