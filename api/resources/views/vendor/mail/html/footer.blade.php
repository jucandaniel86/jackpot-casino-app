<tr>
    <td>
        <table class="footer" style="border-top: 2px solid #2e2e2c;" align="center" width="570" cellpadding="0"
               cellspacing="0" role="presentation" style="margin-top:18px;">
            <tr>
                <td class="content-cell" align="center"
                    style="font-family: Arial, Helvetica, sans-serif; font-size:12px; color:#9a9a9a; padding:18px 8px 36px;">
                    {{ Illuminate\Mail\Markdown::parse($slot) }}
                </td>
            </tr>
        </table>
    </td>
</tr>