<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Atur Ulang Kata Sandi</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; color: #1e293b;">
    <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f1f5f9; padding: 40px 16px;">
        <tr>
            <td align="center">
                <!-- Main Container -->
                <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 580px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0;">
                    
                    <!-- Header Banner -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #064e3b 100%); padding: 32px 36px; text-align: center;">
                            <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="center">
                                        <!-- Security Shield Icon -->
                                        <div style="display: inline-block; width: 48px; height: 48px; line-height: 48px; background-color: rgba(16, 185, 129, 0.2); border-radius: 12px; margin-bottom: 12px;">
                                            <span style="font-size: 24px;">🔐</span>
                                        </div>
                                        <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: #ffffff; letter-spacing: -0.02em;">
                                            {{ config('app.name', 'Sistem Disposisi Surat') }}
                                        </h1>
                                        <p style="margin: 6px 0 0 0; font-size: 13px; color: #34d399;">
                                            Keamanan Akun & Pemulihan Akses
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 36px 36px 28px 36px;">
                            <h2 style="margin: 0 0 16px 0; font-size: 18px; font-weight: 700; color: #0f172a;">
                                Halo, {{ $user->name ?? 'Pengguna' }}!
                            </h2>
                            <p style="margin: 0 0 20px 0; font-size: 14px; line-height: 1.6; color: #475569;">
                                Kami menerima permintaan untuk melakukan pengaturan ulang kata sandi pada akun Anda di <strong>{{ config('app.name', 'Sistem Disposisi Surat') }}</strong>. Silakan klik tombol di bawah ini untuk membuat kata sandi baru:
                            </p>

                            <!-- Primary CTA Button -->
                            <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="margin: 28px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $url }}" target="_blank" style="display: inline-block; background: linear-gradient(135deg, #059669 0%, #0d9488 100%); color: #ffffff; font-size: 14px; font-weight: 600; text-decoration: none; padding: 14px 32px; border-radius: 10px; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);">
                                            Atur Ulang Kata Sandi
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- Security / Validity Info Box -->
                            <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; margin: 24px 0 0 0;">
                                <tr>
                                    <td style="padding: 16px;">
                                        <p style="margin: 0 0 8px 0; font-size: 12px; line-height: 1.5; color: #64748b;">
                                            ⏱️ <strong>Kedaluwarsa:</strong> Tautan ini akan kedaluwarsa secara otomatis dalam <strong>{{ $count ?? 60 }} menit</strong>.
                                        </p>
                                        <p style="margin: 0; font-size: 12px; line-height: 1.5; color: #64748b;">
                                            🛡️ <strong>Keamanan:</strong> Jika Anda tidak mengajukan permintaan ini, tidak ada tindakan yang diperlukan. Akun Anda tetap aman dan kata sandi Anda tidak berubah.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Raw URL Fallback -->
                            <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #f1f5f9;">
                                <p style="margin: 0 0 8px 0; font-size: 12px; color: #94a3b8;">
                                    Jika Anda mengalami kendala saat menekan tombol di atas, salin dan buka tautan berikut:
                                </p>
                                <p style="margin: 0; font-size: 11px; line-height: 1.4; color: #0d9488; word-break: break-all;">
                                    <a href="{{ $url }}" style="color: #0d9488; text-decoration: underline;">{{ $url }}</a>
                                </p>
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 24px 36px; border-top: 1px solid #e2e8f0; text-align: center;">
                            <p style="margin: 0 0 4px 0; font-size: 12px; font-weight: 500; color: #64748b;">
                                {{ config('app.name', 'Sistem Disposisi Surat') }}
                            </p>
                            <p style="margin: 0; font-size: 11px; color: #94a3b8;">
                                Email keamanan otomatis. Mohon untuk tidak membalas langsung ke pesan ini.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
