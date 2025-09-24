import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\AdminController::view
 * @see app/Http/Controllers/AdminController.php:1123
 * @route '/admin/players/{player}'
 */
export const view = (args: { player: number | { id: number } } | [player: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: view.url(args, options),
    method: 'get',
})

view.definition = {
    methods: ["get","head"],
    url: '/admin/players/{player}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AdminController::view
 * @see app/Http/Controllers/AdminController.php:1123
 * @route '/admin/players/{player}'
 */
view.url = (args: { player: number | { id: number } } | [player: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { player: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { player: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    player: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        player: typeof args.player === 'object'
                ? args.player.id
                : args.player,
                }

    return view.definition.url
            .replace('{player}', parsedArgs.player.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::view
 * @see app/Http/Controllers/AdminController.php:1123
 * @route '/admin/players/{player}'
 */
view.get = (args: { player: number | { id: number } } | [player: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: view.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AdminController::view
 * @see app/Http/Controllers/AdminController.php:1123
 * @route '/admin/players/{player}'
 */
view.head = (args: { player: number | { id: number } } | [player: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: view.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\AdminController::view
 * @see app/Http/Controllers/AdminController.php:1123
 * @route '/admin/players/{player}'
 */
    const viewForm = (args: { player: number | { id: number } } | [player: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: view.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\AdminController::view
 * @see app/Http/Controllers/AdminController.php:1123
 * @route '/admin/players/{player}'
 */
        viewForm.get = (args: { player: number | { id: number } } | [player: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: view.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\AdminController::view
 * @see app/Http/Controllers/AdminController.php:1123
 * @route '/admin/players/{player}'
 */
        viewForm.head = (args: { player: number | { id: number } } | [player: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: view.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    view.form = viewForm
/**
* @see \App\Http\Controllers\AdminController::destroy
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/players/{player}'
 */
export const destroy = (args: { player: string | number } | [player: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/admin/players/{player}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\AdminController::destroy
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/players/{player}'
 */
destroy.url = (args: { player: string | number } | [player: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { player: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    player: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        player: args.player,
                }

    return destroy.definition.url
            .replace('{player}', parsedArgs.player.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::destroy
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/players/{player}'
 */
destroy.delete = (args: { player: string | number } | [player: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

    /**
* @see \App\Http\Controllers\AdminController::destroy
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/players/{player}'
 */
    const destroyForm = (args: { player: string | number } | [player: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: destroy.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'DELETE',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\AdminController::destroy
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/players/{player}'
 */
        destroyForm.delete = (args: { player: string | number } | [player: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: destroy.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'DELETE',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    destroy.form = destroyForm
const players = {
    view,
destroy,
}

export default players