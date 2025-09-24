import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\AdminController::counties
 * @see app/Http/Controllers/AdminController.php:810
 * @route '/admin/api/counties/{region}'
 */
export const counties = (args: { region: string | number } | [region: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: counties.url(args, options),
    method: 'get',
})

counties.definition = {
    methods: ["get","head"],
    url: '/admin/api/counties/{region}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AdminController::counties
 * @see app/Http/Controllers/AdminController.php:810
 * @route '/admin/api/counties/{region}'
 */
counties.url = (args: { region: string | number } | [region: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { region: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    region: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        region: args.region,
                }

    return counties.definition.url
            .replace('{region}', parsedArgs.region.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::counties
 * @see app/Http/Controllers/AdminController.php:810
 * @route '/admin/api/counties/{region}'
 */
counties.get = (args: { region: string | number } | [region: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: counties.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AdminController::counties
 * @see app/Http/Controllers/AdminController.php:810
 * @route '/admin/api/counties/{region}'
 */
counties.head = (args: { region: string | number } | [region: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: counties.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\AdminController::counties
 * @see app/Http/Controllers/AdminController.php:810
 * @route '/admin/api/counties/{region}'
 */
    const countiesForm = (args: { region: string | number } | [region: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: counties.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\AdminController::counties
 * @see app/Http/Controllers/AdminController.php:810
 * @route '/admin/api/counties/{region}'
 */
        countiesForm.get = (args: { region: string | number } | [region: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: counties.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\AdminController::counties
 * @see app/Http/Controllers/AdminController.php:810
 * @route '/admin/api/counties/{region}'
 */
        countiesForm.head = (args: { region: string | number } | [region: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: counties.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    counties.form = countiesForm
/**
* @see \App\Http\Controllers\AdminController::communities
 * @see app/Http/Controllers/AdminController.php:825
 * @route '/admin/api/communities/{county}'
 */
export const communities = (args: { county: string | number } | [county: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: communities.url(args, options),
    method: 'get',
})

communities.definition = {
    methods: ["get","head"],
    url: '/admin/api/communities/{county}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AdminController::communities
 * @see app/Http/Controllers/AdminController.php:825
 * @route '/admin/api/communities/{county}'
 */
communities.url = (args: { county: string | number } | [county: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { county: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    county: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        county: args.county,
                }

    return communities.definition.url
            .replace('{county}', parsedArgs.county.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::communities
 * @see app/Http/Controllers/AdminController.php:825
 * @route '/admin/api/communities/{county}'
 */
communities.get = (args: { county: string | number } | [county: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: communities.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AdminController::communities
 * @see app/Http/Controllers/AdminController.php:825
 * @route '/admin/api/communities/{county}'
 */
communities.head = (args: { county: string | number } | [county: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: communities.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\AdminController::communities
 * @see app/Http/Controllers/AdminController.php:825
 * @route '/admin/api/communities/{county}'
 */
    const communitiesForm = (args: { county: string | number } | [county: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: communities.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\AdminController::communities
 * @see app/Http/Controllers/AdminController.php:825
 * @route '/admin/api/communities/{county}'
 */
        communitiesForm.get = (args: { county: string | number } | [county: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: communities.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\AdminController::communities
 * @see app/Http/Controllers/AdminController.php:825
 * @route '/admin/api/communities/{county}'
 */
        communitiesForm.head = (args: { county: string | number } | [county: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: communities.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    communities.form = communitiesForm
const api = {
    counties,
communities,
}

export default api