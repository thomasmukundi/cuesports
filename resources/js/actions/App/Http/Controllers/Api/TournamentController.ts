import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\TournamentController::index
 * @see app/Http/Controllers/Api/TournamentController.php:15
 * @route '/api/tournaments'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/tournaments',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\TournamentController::index
 * @see app/Http/Controllers/Api/TournamentController.php:15
 * @route '/api/tournaments'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\TournamentController::index
 * @see app/Http/Controllers/Api/TournamentController.php:15
 * @route '/api/tournaments'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Api\TournamentController::index
 * @see app/Http/Controllers/Api/TournamentController.php:15
 * @route '/api/tournaments'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Api\TournamentController::index
 * @see app/Http/Controllers/Api/TournamentController.php:15
 * @route '/api/tournaments'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Api\TournamentController::index
 * @see app/Http/Controllers/Api/TournamentController.php:15
 * @route '/api/tournaments'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Api\TournamentController::index
 * @see app/Http/Controllers/Api/TournamentController.php:15
 * @route '/api/tournaments'
 */
        indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    index.form = indexForm
/**
* @see \App\Http\Controllers\Api\TournamentController::featured
 * @see app/Http/Controllers/Api/TournamentController.php:82
 * @route '/api/tournaments/featured'
 */
export const featured = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: featured.url(options),
    method: 'get',
})

featured.definition = {
    methods: ["get","head"],
    url: '/api/tournaments/featured',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\TournamentController::featured
 * @see app/Http/Controllers/Api/TournamentController.php:82
 * @route '/api/tournaments/featured'
 */
featured.url = (options?: RouteQueryOptions) => {
    return featured.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\TournamentController::featured
 * @see app/Http/Controllers/Api/TournamentController.php:82
 * @route '/api/tournaments/featured'
 */
featured.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: featured.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Api\TournamentController::featured
 * @see app/Http/Controllers/Api/TournamentController.php:82
 * @route '/api/tournaments/featured'
 */
featured.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: featured.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Api\TournamentController::featured
 * @see app/Http/Controllers/Api/TournamentController.php:82
 * @route '/api/tournaments/featured'
 */
    const featuredForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: featured.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Api\TournamentController::featured
 * @see app/Http/Controllers/Api/TournamentController.php:82
 * @route '/api/tournaments/featured'
 */
        featuredForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: featured.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Api\TournamentController::featured
 * @see app/Http/Controllers/Api/TournamentController.php:82
 * @route '/api/tournaments/featured'
 */
        featuredForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: featured.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    featured.form = featuredForm
/**
* @see \App\Http\Controllers\Api\TournamentController::register
 * @see app/Http/Controllers/Api/TournamentController.php:114
 * @route '/api/tournaments/{tournament}/register'
 */
export const register = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: register.url(args, options),
    method: 'post',
})

register.definition = {
    methods: ["post"],
    url: '/api/tournaments/{tournament}/register',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\TournamentController::register
 * @see app/Http/Controllers/Api/TournamentController.php:114
 * @route '/api/tournaments/{tournament}/register'
 */
register.url = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { tournament: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { tournament: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    tournament: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        tournament: typeof args.tournament === 'object'
                ? args.tournament.id
                : args.tournament,
                }

    return register.definition.url
            .replace('{tournament}', parsedArgs.tournament.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\TournamentController::register
 * @see app/Http/Controllers/Api/TournamentController.php:114
 * @route '/api/tournaments/{tournament}/register'
 */
register.post = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: register.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Api\TournamentController::register
 * @see app/Http/Controllers/Api/TournamentController.php:114
 * @route '/api/tournaments/{tournament}/register'
 */
    const registerForm = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: register.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Api\TournamentController::register
 * @see app/Http/Controllers/Api/TournamentController.php:114
 * @route '/api/tournaments/{tournament}/register'
 */
        registerForm.post = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: register.url(args, options),
            method: 'post',
        })
    
    register.form = registerForm
/**
* @see \App\Http\Controllers\Api\TournamentController::myRegistrations
 * @see app/Http/Controllers/Api/TournamentController.php:164
 * @route '/api/tournaments/my-registrations'
 */
export const myRegistrations = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: myRegistrations.url(options),
    method: 'get',
})

myRegistrations.definition = {
    methods: ["get","head"],
    url: '/api/tournaments/my-registrations',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\TournamentController::myRegistrations
 * @see app/Http/Controllers/Api/TournamentController.php:164
 * @route '/api/tournaments/my-registrations'
 */
myRegistrations.url = (options?: RouteQueryOptions) => {
    return myRegistrations.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\TournamentController::myRegistrations
 * @see app/Http/Controllers/Api/TournamentController.php:164
 * @route '/api/tournaments/my-registrations'
 */
myRegistrations.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: myRegistrations.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Api\TournamentController::myRegistrations
 * @see app/Http/Controllers/Api/TournamentController.php:164
 * @route '/api/tournaments/my-registrations'
 */
myRegistrations.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: myRegistrations.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Api\TournamentController::myRegistrations
 * @see app/Http/Controllers/Api/TournamentController.php:164
 * @route '/api/tournaments/my-registrations'
 */
    const myRegistrationsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: myRegistrations.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Api\TournamentController::myRegistrations
 * @see app/Http/Controllers/Api/TournamentController.php:164
 * @route '/api/tournaments/my-registrations'
 */
        myRegistrationsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: myRegistrations.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Api\TournamentController::myRegistrations
 * @see app/Http/Controllers/Api/TournamentController.php:164
 * @route '/api/tournaments/my-registrations'
 */
        myRegistrationsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: myRegistrations.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    myRegistrations.form = myRegistrationsForm
const TournamentController = { index, featured, register, myRegistrations }

export default TournamentController