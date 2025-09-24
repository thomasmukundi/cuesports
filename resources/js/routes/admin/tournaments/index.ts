import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\AdminController::create
 * @see app/Http/Controllers/AdminController.php:335
 * @route '/admin/tournaments/create'
 */
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/admin/tournaments/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AdminController::create
 * @see app/Http/Controllers/AdminController.php:335
 * @route '/admin/tournaments/create'
 */
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::create
 * @see app/Http/Controllers/AdminController.php:335
 * @route '/admin/tournaments/create'
 */
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AdminController::create
 * @see app/Http/Controllers/AdminController.php:335
 * @route '/admin/tournaments/create'
 */
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\AdminController::create
 * @see app/Http/Controllers/AdminController.php:335
 * @route '/admin/tournaments/create'
 */
    const createForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: create.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\AdminController::create
 * @see app/Http/Controllers/AdminController.php:335
 * @route '/admin/tournaments/create'
 */
        createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: create.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\AdminController::create
 * @see app/Http/Controllers/AdminController.php:335
 * @route '/admin/tournaments/create'
 */
        createForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: create.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    create.form = createForm
/**
* @see \App\Http\Controllers\AdminController::store
 * @see app/Http/Controllers/AdminController.php:345
 * @route '/admin/tournaments'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/admin/tournaments',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AdminController::store
 * @see app/Http/Controllers/AdminController.php:345
 * @route '/admin/tournaments'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::store
 * @see app/Http/Controllers/AdminController.php:345
 * @route '/admin/tournaments'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\AdminController::store
 * @see app/Http/Controllers/AdminController.php:345
 * @route '/admin/tournaments'
 */
    const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: store.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\AdminController::store
 * @see app/Http/Controllers/AdminController.php:345
 * @route '/admin/tournaments'
 */
        storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: store.url(options),
            method: 'post',
        })
    
    store.form = storeForm
/**
* @see \App\Http\Controllers\AdminController::view
 * @see app/Http/Controllers/AdminController.php:849
 * @route '/admin/tournaments/{tournament}'
 */
export const view = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: view.url(args, options),
    method: 'get',
})

view.definition = {
    methods: ["get","head"],
    url: '/admin/tournaments/{tournament}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AdminController::view
 * @see app/Http/Controllers/AdminController.php:849
 * @route '/admin/tournaments/{tournament}'
 */
view.url = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return view.definition.url
            .replace('{tournament}', parsedArgs.tournament.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::view
 * @see app/Http/Controllers/AdminController.php:849
 * @route '/admin/tournaments/{tournament}'
 */
view.get = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: view.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AdminController::view
 * @see app/Http/Controllers/AdminController.php:849
 * @route '/admin/tournaments/{tournament}'
 */
view.head = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: view.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\AdminController::view
 * @see app/Http/Controllers/AdminController.php:849
 * @route '/admin/tournaments/{tournament}'
 */
    const viewForm = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: view.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\AdminController::view
 * @see app/Http/Controllers/AdminController.php:849
 * @route '/admin/tournaments/{tournament}'
 */
        viewForm.get = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: view.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\AdminController::view
 * @see app/Http/Controllers/AdminController.php:849
 * @route '/admin/tournaments/{tournament}'
 */
        viewForm.head = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\AdminController::edit
 * @see app/Http/Controllers/AdminController.php:406
 * @route '/admin/tournaments/{tournament}/edit'
 */
export const edit = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/admin/tournaments/{tournament}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AdminController::edit
 * @see app/Http/Controllers/AdminController.php:406
 * @route '/admin/tournaments/{tournament}/edit'
 */
edit.url = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return edit.definition.url
            .replace('{tournament}', parsedArgs.tournament.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::edit
 * @see app/Http/Controllers/AdminController.php:406
 * @route '/admin/tournaments/{tournament}/edit'
 */
edit.get = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AdminController::edit
 * @see app/Http/Controllers/AdminController.php:406
 * @route '/admin/tournaments/{tournament}/edit'
 */
edit.head = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\AdminController::edit
 * @see app/Http/Controllers/AdminController.php:406
 * @route '/admin/tournaments/{tournament}/edit'
 */
    const editForm = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: edit.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\AdminController::edit
 * @see app/Http/Controllers/AdminController.php:406
 * @route '/admin/tournaments/{tournament}/edit'
 */
        editForm.get = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: edit.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\AdminController::edit
 * @see app/Http/Controllers/AdminController.php:406
 * @route '/admin/tournaments/{tournament}/edit'
 */
        editForm.head = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: edit.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    edit.form = editForm
/**
* @see \App\Http\Controllers\AdminController::update
 * @see app/Http/Controllers/AdminController.php:416
 * @route '/admin/tournaments/{tournament}'
 */
export const update = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/admin/tournaments/{tournament}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\AdminController::update
 * @see app/Http/Controllers/AdminController.php:416
 * @route '/admin/tournaments/{tournament}'
 */
update.url = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return update.definition.url
            .replace('{tournament}', parsedArgs.tournament.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::update
 * @see app/Http/Controllers/AdminController.php:416
 * @route '/admin/tournaments/{tournament}'
 */
update.put = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

    /**
* @see \App\Http\Controllers\AdminController::update
 * @see app/Http/Controllers/AdminController.php:416
 * @route '/admin/tournaments/{tournament}'
 */
    const updateForm = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: update.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'PUT',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\AdminController::update
 * @see app/Http/Controllers/AdminController.php:416
 * @route '/admin/tournaments/{tournament}'
 */
        updateForm.put = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: update.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'PUT',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    update.form = updateForm
/**
* @see \App\Http\Controllers\AdminController::destroy
 * @see app/Http/Controllers/AdminController.php:476
 * @route '/admin/tournaments/{tournament}'
 */
export const destroy = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/admin/tournaments/{tournament}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\AdminController::destroy
 * @see app/Http/Controllers/AdminController.php:476
 * @route '/admin/tournaments/{tournament}'
 */
destroy.url = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return destroy.definition.url
            .replace('{tournament}', parsedArgs.tournament.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::destroy
 * @see app/Http/Controllers/AdminController.php:476
 * @route '/admin/tournaments/{tournament}'
 */
destroy.delete = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

    /**
* @see \App\Http\Controllers\AdminController::destroy
 * @see app/Http/Controllers/AdminController.php:476
 * @route '/admin/tournaments/{tournament}'
 */
    const destroyForm = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
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
 * @see app/Http/Controllers/AdminController.php:476
 * @route '/admin/tournaments/{tournament}'
 */
        destroyForm.delete = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: destroy.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'DELETE',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    destroy.form = destroyForm
/**
* @see \App\Http\Controllers\AdminController::initialize
 * @see app/Http/Controllers/AdminController.php:1053
 * @route '/admin/tournaments/{tournament}/initialize/{level}'
 */
export const initialize = (args: { tournament: number | { id: number }, level: string | number } | [tournament: number | { id: number }, level: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: initialize.url(args, options),
    method: 'post',
})

initialize.definition = {
    methods: ["post"],
    url: '/admin/tournaments/{tournament}/initialize/{level}',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AdminController::initialize
 * @see app/Http/Controllers/AdminController.php:1053
 * @route '/admin/tournaments/{tournament}/initialize/{level}'
 */
initialize.url = (args: { tournament: number | { id: number }, level: string | number } | [tournament: number | { id: number }, level: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
                    tournament: args[0],
                    level: args[1],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        tournament: typeof args.tournament === 'object'
                ? args.tournament.id
                : args.tournament,
                                level: args.level,
                }

    return initialize.definition.url
            .replace('{tournament}', parsedArgs.tournament.toString())
            .replace('{level}', parsedArgs.level.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::initialize
 * @see app/Http/Controllers/AdminController.php:1053
 * @route '/admin/tournaments/{tournament}/initialize/{level}'
 */
initialize.post = (args: { tournament: number | { id: number }, level: string | number } | [tournament: number | { id: number }, level: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: initialize.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\AdminController::initialize
 * @see app/Http/Controllers/AdminController.php:1053
 * @route '/admin/tournaments/{tournament}/initialize/{level}'
 */
    const initializeForm = (args: { tournament: number | { id: number }, level: string | number } | [tournament: number | { id: number }, level: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: initialize.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\AdminController::initialize
 * @see app/Http/Controllers/AdminController.php:1053
 * @route '/admin/tournaments/{tournament}/initialize/{level}'
 */
        initializeForm.post = (args: { tournament: number | { id: number }, level: string | number } | [tournament: number | { id: number }, level: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: initialize.url(args, options),
            method: 'post',
        })
    
    initialize.form = initializeForm
const tournaments = {
    create,
store,
view,
edit,
update,
destroy,
initialize,
}

export default tournaments