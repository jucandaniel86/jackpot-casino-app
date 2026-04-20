@props(['url', 'logo' => null])
<tr>
    <td class="header" style="padding:32px 0 18px; text-align:center; background: #2e2e2c;">
        <a href="{{ $url }}" style="display:inline-block; text-decoration:none;">
            @if(!empty($logo))
                <img src="{{ $logo }}" alt="Logo" style="width: 140px; height: auto; display: inline-block;">
            @else
                @include('components.logo')
            @endif
            <div style="margin-top:10px; font-family: Arial, Helvetica, sans-serif; font-size:18px; letter-spacing:2px; color:#d4af37;">
                {{ $slot }}
            </div>
        </a>
    </td>
</tr>
