<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse: collapse; margin: 20px 0;">
    @foreach ($rows as $label => $value)
        <tr>
            <td style="width: 38%; border: 1px solid #d1d5db; background: #f9fafb; padding: 10px 12px; font-size: 13px; font-weight: bold; color: #111827;">
                {{ $label }}
            </td>

            <td style="border: 1px solid #d1d5db; padding: 10px 12px; font-size: 13px; color: #111827;">
                {!! $value !!}
            </td>
        </tr>
    @endforeach
</table>