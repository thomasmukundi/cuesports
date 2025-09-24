import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Admin\PlayerController::pendingRegistrations
 * @see app/Http/Controllers/Admin/PlayerController.php:15
 * @route '/api/admin/players/registrations/pending'
 */
export const pendingRegistrations = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: pendingRegistrations.url(options),
    method: 'get',
})

pendingRegistrations.definition = {
    methods: ["get","head"],
    url: '/api/admin/players/registrations/pending',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Admin\PlayerController::pendingRegistrations
 * @see app/Http/Controllers/Admin/PlayerController.php:15
 * @route '/api/admin/players/registrations/pending'
 */
pendingRegistrations.url = (options?: RouteQueryOptions) => {
    return pendingRegistrations.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\PlayerController::pendingRegistrations
 * @see app/Http/Controllers/Admin/PlayerController.php:15
 * @route '/api/admin/players/registrations/pending'
 */
pendingRegistrations.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: pendingRegistrations.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Admin\PlayerController::pendingRegistrations
 * @see app/Http/Controllers/Admin/PlayerController.php:15
 * @route '/api/admin/players/registrations/pending'
 */
pendingRegistrations.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: pendingRegistrations.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Admin\PlayerController::pendingRegistrations
 * @see app/Http/Controllers/Admin/PlayerController.php:15
 * @route '/api/admin/players/registrations/pending'
 */
    const pendingRegistrationsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: pendingRegistrations.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Admin\PlayerController::pendingRegistrations
 * @see app/Http/Controllers/Admin/PlayerController.php:15
 * @route '/api/admin/players/registrations/pending'
 */
        pendingRegistrationsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: pendingRegistrations.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Admin\PlayerController::pendingRegistrations
 * @see app/Http/Controllers/Admin/PlayerController.php:15
 * @route '/api/admin/players/registrations/pending'
 */
        pendingRegistrationsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: pendingRegistrations.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    pendingRegistrations.form = pendingRegistrationsForm
/**
* @see \App\Http\Controllers\Admin\PlayerController::approveRegistration
 * @see app/Http/Controllers/Admin/PlayerController.php:30
 * @route '/api/admin/players/registrations/{registration}/approve'
 */
export const approveRegistration = (args: { registration: string | number } | [registration: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approveRegistration.url(args, options),
    method: 'post',
})

approveRegistration.definition = {
    methods: ["post"],
    url: '/api/admin/players/registrations/{registration}/approve',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Admin\PlayerController::approveRegistration
 * @see app/Http/Controllers/Admin/PlayerController.php:30
 * @route '/api/admin/players/registrations/{registration}/approve'
 */
approveRegistration.url = (args: { registration: string | number } | [registration: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { registration: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    registration: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        registration: args.registration,
                }

    return approveRegistration.definition.url
            .replace('{registration}', parsedArgs.registration.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\PlayerController::approveRegistration
 * @see app/Http/Controllers/Admin/PlayerController.php:30
 * @route '/api/admin/players/registrations/{registration}/approve'
 */
approveRegistration.post = (args: { registration: string | number } | [registration: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approveRegistration.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Admin\PlayerController::approveRegistration
 * @see app/Http/Controllers/Admin/PlayerController.php:30
 * @route '/api/admin/players/registrations/{registration}/approve'
 */
    const approveRegistrationForm = (args: { registration: string | number } | [registration: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: approveRegistration.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Admin\PlayerController::approveRegistration
 * @see app/Http/Controllers/Admin/PlayerController.php:30
 * @route '/api/admin/players/registrations/{registration}/approve'
 */
        approveRegistrationForm.post = (args: { registration: string | number } | [registration: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: approveRegistration.url(args, options),
            method: 'post',
        })
    
    approveRegistration.form = approveRegistrationForm
/**
* @see \App\Http\Controllers\Admin\PlayerController::rejectRegistration
 * @see app/Http/Controllers/Admin/PlayerController.php:55
 * @route '/api/admin/players/registrations/{registration}/reject'
 */
export const rejectRegistration = (args: { registration: string | number } | [registration: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: rejectRegistration.url(args, options),
    method: 'post',
})

rejectRegistration.definition = {
    methods: ["post"],
    url: '/api/admin/players/registrations/{registration}/reject',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Admin\PlayerController::rejectRegistration
 * @see app/Http/Controllers/Admin/PlayerController.php:55
 * @route '/api/admin/players/registrations/{registration}/reject'
 */
rejectRegistration.url = (args: { registration: string | number } | [registration: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { registration: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    registration: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        registration: args.registration,
                }

    return rejectRegistration.definition.url
            .replace('{registration}', parsedArgs.registration.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\PlayerController::rejectRegistration
 * @see app/Http/Controllers/Admin/PlayerController.php:55
 * @route '/api/admin/players/registrations/{registration}/reject'
 */
rejectRegistration.post = (args: { registration: string | number } | [registration: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: rejectRegistration.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Admin\PlayerController::rejectRegistration
 * @see app/Http/Controllers/Admin/PlayerController.php:55
 * @route '/api/admin/players/registrations/{registration}/reject'
 */
    const rejectRegistrationForm = (args: { registration: string | number } | [registration: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: rejectRegistration.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Admin\PlayerController::rejectRegistration
 * @see app/Http/Controllers/Admin/PlayerController.php:55
 * @route '/api/admin/players/registrations/{registration}/reject'
 */
        rejectRegistrationForm.post = (args: { registration: string | number } | [registration: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: rejectRegistration.url(args, options),
            method: 'post',
        })
    
    rejectRegistration.form = rejectRegistrationForm
const PlayerController = { pendingRegistrations, approveRegistration, rejectRegistration }

export default PlayerController