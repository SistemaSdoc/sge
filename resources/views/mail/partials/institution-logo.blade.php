@if (! empty($institutionLogoCid))
    <div style="padding: 24px 24px 0; text-align: center;">
        <img
            src="{{ $institutionLogoCid }}"
            alt="{{ $institutionLogoAlt }}"
            style="display: inline-block; width: auto; max-width: 160px; max-height: 72px; object-fit: contain;"
        >
    </div>
@endif