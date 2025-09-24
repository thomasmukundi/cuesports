import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\VerificationController::sendCode
 * @see app/Http/Controllers/VerificationController.php:18
 * @route '/api/verification/send-code'
 */
export const sendCode = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: sendCode.url(options),
    method: 'post',
})

sendCode.definition = {
    methods: ["post"],
    url: '/api/verification/send-code',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\VerificationController::sendCode
 * @see app/Http/Controllers/VerificationController.php:18
 * @route '/api/verification/send-code'
 */
sendCode.url = (options?: RouteQueryOptions) => {
    return sendCode.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\VerificationController::sendCode
 * @see app/Http/Controllers/VerificationController.php:18
 * @route '/api/verification/send-code'
 */
sendCode.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: sendCode.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\VerificationController::sendCode
 * @see app/Http/Controllers/VerificationController.php:18
 * @route '/api/verification/send-code'
 */
    const sendCodeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: sendCode.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\VerificationController::sendCode
 * @see app/Http/Controllers/VerificationController.php:18
 * @route '/api/verification/send-code'
 */
        sendCodeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: sendCode.url(options),
            method: 'post',
        })
    
    sendCode.form = sendCodeForm
/**
* @see \App\Http\Controllers\VerificationController::verifyCode
 * @see app/Http/Controllers/VerificationController.php:68
 * @route '/api/verification/verify-code'
 */
export const verifyCode = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: verifyCode.url(options),
    method: 'post',
})

verifyCode.definition = {
    methods: ["post"],
    url: '/api/verification/verify-code',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\VerificationController::verifyCode
 * @see app/Http/Controllers/VerificationController.php:68
 * @route '/api/verification/verify-code'
 */
verifyCode.url = (options?: RouteQueryOptions) => {
    return verifyCode.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\VerificationController::verifyCode
 * @see app/Http/Controllers/VerificationController.php:68
 * @route '/api/verification/verify-code'
 */
verifyCode.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: verifyCode.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\VerificationController::verifyCode
 * @see app/Http/Controllers/VerificationController.php:68
 * @route '/api/verification/verify-code'
 */
    const verifyCodeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: verifyCode.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\VerificationController::verifyCode
 * @see app/Http/Controllers/VerificationController.php:68
 * @route '/api/verification/verify-code'
 */
        verifyCodeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: verifyCode.url(options),
            method: 'post',
        })
    
    verifyCode.form = verifyCodeForm
/**
* @see \App\Http\Controllers\VerificationController::resendCode
 * @see app/Http/Controllers/VerificationController.php:184
 * @route '/api/verification/resend-code'
 */
export const resendCode = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resendCode.url(options),
    method: 'post',
})

resendCode.definition = {
    methods: ["post"],
    url: '/api/verification/resend-code',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\VerificationController::resendCode
 * @see app/Http/Controllers/VerificationController.php:184
 * @route '/api/verification/resend-code'
 */
resendCode.url = (options?: RouteQueryOptions) => {
    return resendCode.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\VerificationController::resendCode
 * @see app/Http/Controllers/VerificationController.php:184
 * @route '/api/verification/resend-code'
 */
resendCode.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resendCode.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\VerificationController::resendCode
 * @see app/Http/Controllers/VerificationController.php:184
 * @route '/api/verification/resend-code'
 */
    const resendCodeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: resendCode.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\VerificationController::resendCode
 * @see app/Http/Controllers/VerificationController.php:184
 * @route '/api/verification/resend-code'
 */
        resendCodeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: resendCode.url(options),
            method: 'post',
        })
    
    resendCode.form = resendCodeForm
/**
* @see \App\Http\Controllers\VerificationController::checkStatus
 * @see app/Http/Controllers/VerificationController.php:297
 * @route '/api/verification/check-status'
 */
export const checkStatus = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: checkStatus.url(options),
    method: 'get',
})

checkStatus.definition = {
    methods: ["get","head"],
    url: '/api/verification/check-status',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\VerificationController::checkStatus
 * @see app/Http/Controllers/VerificationController.php:297
 * @route '/api/verification/check-status'
 */
checkStatus.url = (options?: RouteQueryOptions) => {
    return checkStatus.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\VerificationController::checkStatus
 * @see app/Http/Controllers/VerificationController.php:297
 * @route '/api/verification/check-status'
 */
checkStatus.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: checkStatus.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\VerificationController::checkStatus
 * @see app/Http/Controllers/VerificationController.php:297
 * @route '/api/verification/check-status'
 */
checkStatus.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: checkStatus.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\VerificationController::checkStatus
 * @see app/Http/Controllers/VerificationController.php:297
 * @route '/api/verification/check-status'
 */
    const checkStatusForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: checkStatus.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\VerificationController::checkStatus
 * @see app/Http/Controllers/VerificationController.php:297
 * @route '/api/verification/check-status'
 */
        checkStatusForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: checkStatus.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\VerificationController::checkStatus
 * @see app/Http/Controllers/VerificationController.php:297
 * @route '/api/verification/check-status'
 */
        checkStatusForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: checkStatus.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    checkStatus.form = checkStatusForm
/**
* @see \App\Http\Controllers\VerificationController::resetPassword
 * @see app/Http/Controllers/VerificationController.php:211
 * @route '/api/reset-password'
 */
export const resetPassword = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resetPassword.url(options),
    method: 'post',
})

resetPassword.definition = {
    methods: ["post"],
    url: '/api/reset-password',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\VerificationController::resetPassword
 * @see app/Http/Controllers/VerificationController.php:211
 * @route '/api/reset-password'
 */
resetPassword.url = (options?: RouteQueryOptions) => {
    return resetPassword.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\VerificationController::resetPassword
 * @see app/Http/Controllers/VerificationController.php:211
 * @route '/api/reset-password'
 */
resetPassword.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resetPassword.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\VerificationController::resetPassword
 * @see app/Http/Controllers/VerificationController.php:211
 * @route '/api/reset-password'
 */
    const resetPasswordForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: resetPassword.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\VerificationController::resetPassword
 * @see app/Http/Controllers/VerificationController.php:211
 * @route '/api/reset-password'
 */
        resetPasswordForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: resetPassword.url(options),
            method: 'post',
        })
    
    resetPassword.form = resetPasswordForm
const VerificationController = { sendCode, verifyCode, resendCode, checkStatus, resetPassword }

export default VerificationController