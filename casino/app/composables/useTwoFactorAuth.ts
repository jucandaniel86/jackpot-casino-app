export const useTwoFactorAuth = () => {
  const getTwoFactorPayload = (payload: any = {}) => {
    const safePayload = payload || {}

    const requiresTwoFactor = Boolean(
      safePayload.requires_2fa ||
        safePayload.two_factor_required ||
        safePayload.requiresTwoFactor ||
        safePayload.require_two_factor ||
        safePayload.status === '2fa_setup_required' ||
        safePayload.login_token,
    )

    return {
      requiresTwoFactor,
      loginToken:
        safePayload.login_token ||
        safePayload.loginToken ||
        safePayload.two_factor_token ||
        safePayload.setup_token ||
        '',
      message: safePayload.message || '',
      setupRequired: safePayload.status === '2fa_setup_required',
      otpauthUrl: safePayload.otpauth_url || '',
      secret: safePayload.secret || '',
      expiresIn: safePayload.expires_in || null,
    }
  }

  return {
    getTwoFactorPayload,
  }
}
