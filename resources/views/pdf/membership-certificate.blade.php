<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 0; }
    html, body { margin: 0; padding: 0; font-family: "DejaVu Sans", sans-serif; color: #1f2937; }
    .sheet { width: 100%; height: 100%; }
    .frame-outer { position: absolute; left: 22px; top: 22px; right: 22px; bottom: 22px; border: 3px solid #1e6b52; }
    .frame-inner { position: absolute; left: 34px; top: 34px; right: 34px; bottom: 34px; border: 1px solid #b8860b; }
    .content { position: absolute; left: 60px; top: 56px; right: 60px; bottom: 56px; text-align: center; }
    .logo { height: 70px; }
    .org { font-size: 20px; font-weight: bold; color: #1e6b52; margin-top: 6px; }
    .org-address { font-size: 10px; color: #6b7280; margin-top: 2px; }
    .title { font-size: 34px; color: #1e6b52; margin-top: 18px; letter-spacing: 2px; text-transform: uppercase; }
    .divider { width: 180px; height: 2px; background: #b8860b; margin: 10px auto 14px auto; }
    .lead { font-size: 13px; color: #4b5563; }
    .name { font-size: 32px; font-weight: bold; color: #111827; margin: 12px 0 10px 0; }
    .body-text { font-size: 13px; color: #374151; line-height: 1.5; margin: 0 40px; }
    .designation { font-size: 15px; font-weight: bold; color: #1e6b52; margin-top: 6px; }
    .meta { margin-top: 22px; font-size: 11px; color: #374151; }
    .meta td { padding: 2px 16px; }
    .meta .label { color: #6b7280; text-align: right; }
    .meta .value { font-weight: bold; text-align: left; }
    .signatures { position: absolute; left: 0; right: 0; bottom: 0; }
    .signatures td { width: 50%; text-align: center; vertical-align: bottom; font-size: 11px; }
    .sign-line { border-top: 1px solid #374151; width: 200px; margin: 0 auto; padding-top: 4px; }
    .sign-name { font-weight: bold; color: #111827; }
    .stamp { height: 80px; margin-bottom: -20px; }
</style>
</head>
<body>
<div class="sheet">
    <div class="frame-outer"></div>
    <div class="frame-inner"></div>

    <div class="content">
        @if ($logoUri !== '')
            <img class="logo" src="{{ $logoUri }}" alt="">
        @endif
        <div class="org">{{ $orgName }}</div>
        @if ($orgAddress !== '')
            <div class="org-address">{{ $orgAddress }}</div>
        @endif

        <div class="title">Certificate of Membership</div>
        <div class="divider"></div>

        <div class="lead">This is to certify that</div>
        <div class="name">{{ $memberName }}</div>
        <div class="body-text">
            is a registered member of {{ $orgName }}
            @if ($designation !== '')
                serving in the capacity of <strong>{{ $designation }}</strong>
            @endif
            and is recognised as a member of the organisation from the date of issue shown below.
        </div>

        <table class="meta" align="center" cellspacing="0" cellpadding="0" style="margin-left:auto;margin-right:auto;">
            <tr>
                <td class="label">Certificate No.</td>
                <td class="value">{{ $certificateNo }}</td>
                <td class="label">Issued on</td>
                <td class="value">{{ $issuedOn }}</td>
            </tr>
            <tr>
                <td class="label">Valid until</td>
                <td class="value" colspan="3">{{ $validUntil }}</td>
            </tr>
        </table>
    </div>

    <table class="signatures" cellspacing="0" cellpadding="0" style="position:absolute;left:60px;right:60px;bottom:70px;width:720px;">
        <tr>
            <td>
                @if ($stampUri !== '')
                    <img class="stamp" src="{{ $stampUri }}" alt="">
                @endif
            </td>
            <td>
                <div class="sign-line">
                    <div class="sign-name">{{ $signatory !== '' ? $signatory : 'Authorised signatory' }}</div>
                    @if ($signatoryRole !== '')
                        <div>{{ $signatoryRole }}</div>
                    @endif
                    <div>{{ $orgName }}</div>
                </div>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
