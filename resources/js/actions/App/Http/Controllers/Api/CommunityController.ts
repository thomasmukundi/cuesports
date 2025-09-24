import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\CommunityController::index
 * @see app/Http/Controllers/Api/CommunityController.php:16
 * @route '/api/communities/list'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/communities/list',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\CommunityController::index
 * @see app/Http/Controllers/Api/CommunityController.php:16
 * @route '/api/communities/list'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\CommunityController::index
 * @see app/Http/Controllers/Api/CommunityController.php:16
 * @route '/api/communities/list'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Api\CommunityController::index
 * @see app/Http/Controllers/Api/CommunityController.php:16
 * @route '/api/communities/list'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Api\CommunityController::index
 * @see app/Http/Controllers/Api/CommunityController.php:16
 * @route '/api/communities/list'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Api\CommunityController::index
 * @see app/Http/Controllers/Api/CommunityController.php:16
 * @route '/api/communities/list'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Api\CommunityController::index
 * @see app/Http/Controllers/Api/CommunityController.php:16
 * @route '/api/communities/list'
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
* @see \App\Http\Controllers\Api\CommunityController::show
 * @see app/Http/Controllers/Api/CommunityController.php:72
 * @route '/api/communities/{community}'
 */
export const show = (args: { community: number | { id: number } } | [community: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/communities/{community}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\CommunityController::show
 * @see app/Http/Controllers/Api/CommunityController.php:72
 * @route '/api/communities/{community}'
 */
show.url = (args: { community: number | { id: number } } | [community: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { community: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { community: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    community: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        community: typeof args.community === 'object'
                ? args.community.id
                : args.community,
                }

    return show.definition.url
            .replace('{community}', parsedArgs.community.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\CommunityController::show
 * @see app/Http/Controllers/Api/CommunityController.php:72
 * @route '/api/communities/{community}'
 */
show.get = (args: { community: number | { id: number } } | [community: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Api\CommunityController::show
 * @see app/Http/Controllers/Api/CommunityController.php:72
 * @route '/api/communities/{community}'
 */
show.head = (args: { community: number | { id: number } } | [community: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Api\CommunityController::show
 * @see app/Http/Controllers/Api/CommunityController.php:72
 * @route '/api/communities/{community}'
 */
    const showForm = (args: { community: number | { id: number } } | [community: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: show.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Api\CommunityController::show
 * @see app/Http/Controllers/Api/CommunityController.php:72
 * @route '/api/communities/{community}'
 */
        showForm.get = (args: { community: number | { id: number } } | [community: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: show.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Api\CommunityController::show
 * @see app/Http/Controllers/Api/CommunityController.php:72
 * @route '/api/communities/{community}'
 */
        showForm.head = (args: { community: number | { id: number } } | [community: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: show.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    show.form = showForm
const CommunityController = { index, show }

export default CommunityController