import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\AuthController::register
 * @see app/Http/Controllers/Api/AuthController.php:18
 * @route '/api/auth/register'
 */
export const register = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: register.url(options),
    method: 'post',
})

register.definition = {
    methods: ["post"],
    url: '/api/auth/register',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\AuthController::register
 * @see app/Http/Controllers/Api/AuthController.php:18
 * @route '/api/auth/register'
 */
register.url = (options?: RouteQueryOptions) => {
    return register.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\AuthController::register
 * @see app/Http/Controllers/Api/AuthController.php:18
 * @route '/api/auth/register'
 */
register.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: register.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Api\AuthController::register
 * @see app/Http/Controllers/Api/AuthController.php:18
 * @route '/api/auth/register'
 */
    const registerForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: register.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Api\AuthController::register
 * @see app/Http/Controllers/Api/AuthController.php:18
 * @route '/api/auth/register'
 */
        registerForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: register.url(options),
            method: 'post',
        })
    
    register.form = registerForm
/**
* @see \App\Http\Controllers\Api\AuthController::login
 * @see app/Http/Controllers/Api/AuthController.php:114
 * @route '/api/auth/login'
 */
export const login = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: login.url(options),
    method: 'post',
})

login.definition = {
    methods: ["post"],
    url: '/api/auth/login',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\AuthController::login
 * @see app/Http/Controllers/Api/AuthController.php:114
 * @route '/api/auth/login'
 */
login.url = (options?: RouteQueryOptions) => {
    return login.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\AuthController::login
 * @see app/Http/Controllers/Api/AuthController.php:114
 * @route '/api/auth/login'
 */
login.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: login.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Api\AuthController::login
 * @see app/Http/Controllers/Api/AuthController.php:114
 * @route '/api/auth/login'
 */
    const loginForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: login.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Api\AuthController::login
 * @see app/Http/Controllers/Api/AuthController.php:114
 * @route '/api/auth/login'
 */
        loginForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: login.url(options),
            method: 'post',
        })
    
    login.form = loginForm
/**
* @see \App\Http\Controllers\Api\AuthController::logout
 * @see app/Http/Controllers/Api/AuthController.php:185
 * @route '/api/auth/logout'
 */
export const logout = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: logout.url(options),
    method: 'post',
})

logout.definition = {
    methods: ["post"],
    url: '/api/auth/logout',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\AuthController::logout
 * @see app/Http/Controllers/Api/AuthController.php:185
 * @route '/api/auth/logout'
 */
logout.url = (options?: RouteQueryOptions) => {
    return logout.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\AuthController::logout
 * @see app/Http/Controllers/Api/AuthController.php:185
 * @route '/api/auth/logout'
 */
logout.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: logout.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Api\AuthController::logout
 * @see app/Http/Controllers/Api/AuthController.php:185
 * @route '/api/auth/logout'
 */
    const logoutForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: logout.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Api\AuthController::logout
 * @see app/Http/Controllers/Api/AuthController.php:185
 * @route '/api/auth/logout'
 */
        logoutForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: logout.url(options),
            method: 'post',
        })
    
    logout.form = logoutForm
/**
* @see \App\Http\Controllers\Api\AuthController::refresh
 * @see app/Http/Controllers/Api/AuthController.php:242
 * @route '/api/auth/refresh'
 */
export const refresh = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: refresh.url(options),
    method: 'post',
})

refresh.definition = {
    methods: ["post"],
    url: '/api/auth/refresh',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\AuthController::refresh
 * @see app/Http/Controllers/Api/AuthController.php:242
 * @route '/api/auth/refresh'
 */
refresh.url = (options?: RouteQueryOptions) => {
    return refresh.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\AuthController::refresh
 * @see app/Http/Controllers/Api/AuthController.php:242
 * @route '/api/auth/refresh'
 */
refresh.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: refresh.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Api\AuthController::refresh
 * @see app/Http/Controllers/Api/AuthController.php:242
 * @route '/api/auth/refresh'
 */
    const refreshForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: refresh.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Api\AuthController::refresh
 * @see app/Http/Controllers/Api/AuthController.php:242
 * @route '/api/auth/refresh'
 */
        refreshForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: refresh.url(options),
            method: 'post',
        })
    
    refresh.form = refreshForm
/**
* @see \App\Http\Controllers\Api\AuthController::me
 * @see app/Http/Controllers/Api/AuthController.php:160
 * @route '/api/auth/me'
 */
export const me = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: me.url(options),
    method: 'get',
})

me.definition = {
    methods: ["get","head"],
    url: '/api/auth/me',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\AuthController::me
 * @see app/Http/Controllers/Api/AuthController.php:160
 * @route '/api/auth/me'
 */
me.url = (options?: RouteQueryOptions) => {
    return me.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\AuthController::me
 * @see app/Http/Controllers/Api/AuthController.php:160
 * @route '/api/auth/me'
 */
me.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: me.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Api\AuthController::me
 * @see app/Http/Controllers/Api/AuthController.php:160
 * @route '/api/auth/me'
 */
me.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: me.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Api\AuthController::me
 * @see app/Http/Controllers/Api/AuthController.php:160
 * @route '/api/auth/me'
 */
    const meForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: me.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Api\AuthController::me
 * @see app/Http/Controllers/Api/AuthController.php:160
 * @route '/api/auth/me'
 */
        meForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: me.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Api\AuthController::me
 * @see app/Http/Controllers/Api/AuthController.php:160
 * @route '/api/auth/me'
 */
        meForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: me.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    me.form = meForm
/**
* @see \App\Http\Controllers\Api\AuthController::updateFcmToken
 * @see app/Http/Controllers/Api/AuthController.php:261
 * @route '/api/auth/fcm-token'
 */
export const updateFcmToken = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: updateFcmToken.url(options),
    method: 'post',
})

updateFcmToken.definition = {
    methods: ["post"],
    url: '/api/auth/fcm-token',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\AuthController::updateFcmToken
 * @see app/Http/Controllers/Api/AuthController.php:261
 * @route '/api/auth/fcm-token'
 */
updateFcmToken.url = (options?: RouteQueryOptions) => {
    return updateFcmToken.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\AuthController::updateFcmToken
 * @see app/Http/Controllers/Api/AuthController.php:261
 * @route '/api/auth/fcm-token'
 */
updateFcmToken.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: updateFcmToken.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Api\AuthController::updateFcmToken
 * @see app/Http/Controllers/Api/AuthController.php:261
 * @route '/api/auth/fcm-token'
 */
    const updateFcmTokenForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: updateFcmToken.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Api\AuthController::updateFcmToken
 * @see app/Http/Controllers/Api/AuthController.php:261
 * @route '/api/auth/fcm-token'
 */
        updateFcmTokenForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: updateFcmToken.url(options),
            method: 'post',
        })
    
    updateFcmToken.form = updateFcmTokenForm
/**
* @see \App\Http\Controllers\Api\AuthController::removeFcmToken
 * @see app/Http/Controllers/Api/AuthController.php:295
 * @route '/api/auth/fcm-token'
 */
export const removeFcmToken = (options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: removeFcmToken.url(options),
    method: 'delete',
})

removeFcmToken.definition = {
    methods: ["delete"],
    url: '/api/auth/fcm-token',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\Api\AuthController::removeFcmToken
 * @see app/Http/Controllers/Api/AuthController.php:295
 * @route '/api/auth/fcm-token'
 */
removeFcmToken.url = (options?: RouteQueryOptions) => {
    return removeFcmToken.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\AuthController::removeFcmToken
 * @see app/Http/Controllers/Api/AuthController.php:295
 * @route '/api/auth/fcm-token'
 */
removeFcmToken.delete = (options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: removeFcmToken.url(options),
    method: 'delete',
})

    /**
* @see \App\Http\Controllers\Api\AuthController::removeFcmToken
 * @see app/Http/Controllers/Api/AuthController.php:295
 * @route '/api/auth/fcm-token'
 */
    const removeFcmTokenForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: removeFcmToken.url({
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'DELETE',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Api\AuthController::removeFcmToken
 * @see app/Http/Controllers/Api/AuthController.php:295
 * @route '/api/auth/fcm-token'
 */
        removeFcmTokenForm.delete = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: removeFcmToken.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'DELETE',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    removeFcmToken.form = removeFcmTokenForm
const AuthController = { register, login, logout, refresh, me, updateFcmToken, removeFcmToken }

export default AuthController