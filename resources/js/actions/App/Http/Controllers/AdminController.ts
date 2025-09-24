import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\AdminController::dashboard
 * @see app/Http/Controllers/AdminController.php:53
 * @route '/admin/dashboard'
 */
export const dashboard = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
})

dashboard.definition = {
    methods: ["get","head"],
    url: '/admin/dashboard',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AdminController::dashboard
 * @see app/Http/Controllers/AdminController.php:53
 * @route '/admin/dashboard'
 */
dashboard.url = (options?: RouteQueryOptions) => {
    return dashboard.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::dashboard
 * @see app/Http/Controllers/AdminController.php:53
 * @route '/admin/dashboard'
 */
dashboard.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AdminController::dashboard
 * @see app/Http/Controllers/AdminController.php:53
 * @route '/admin/dashboard'
 */
dashboard.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: dashboard.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\AdminController::dashboard
 * @see app/Http/Controllers/AdminController.php:53
 * @route '/admin/dashboard'
 */
    const dashboardForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: dashboard.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\AdminController::dashboard
 * @see app/Http/Controllers/AdminController.php:53
 * @route '/admin/dashboard'
 */
        dashboardForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: dashboard.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\AdminController::dashboard
 * @see app/Http/Controllers/AdminController.php:53
 * @route '/admin/dashboard'
 */
        dashboardForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: dashboard.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    dashboard.form = dashboardForm
/**
* @see \App\Http\Controllers\AdminController::tournaments
 * @see app/Http/Controllers/AdminController.php:101
 * @route '/admin/tournaments'
 */
export const tournaments = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: tournaments.url(options),
    method: 'get',
})

tournaments.definition = {
    methods: ["get","head"],
    url: '/admin/tournaments',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AdminController::tournaments
 * @see app/Http/Controllers/AdminController.php:101
 * @route '/admin/tournaments'
 */
tournaments.url = (options?: RouteQueryOptions) => {
    return tournaments.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::tournaments
 * @see app/Http/Controllers/AdminController.php:101
 * @route '/admin/tournaments'
 */
tournaments.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: tournaments.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AdminController::tournaments
 * @see app/Http/Controllers/AdminController.php:101
 * @route '/admin/tournaments'
 */
tournaments.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: tournaments.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\AdminController::tournaments
 * @see app/Http/Controllers/AdminController.php:101
 * @route '/admin/tournaments'
 */
    const tournamentsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: tournaments.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\AdminController::tournaments
 * @see app/Http/Controllers/AdminController.php:101
 * @route '/admin/tournaments'
 */
        tournamentsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: tournaments.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\AdminController::tournaments
 * @see app/Http/Controllers/AdminController.php:101
 * @route '/admin/tournaments'
 */
        tournamentsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: tournaments.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    tournaments.form = tournamentsForm
/**
* @see \App\Http\Controllers\AdminController::createTournament
 * @see app/Http/Controllers/AdminController.php:335
 * @route '/admin/tournaments/create'
 */
export const createTournament = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: createTournament.url(options),
    method: 'get',
})

createTournament.definition = {
    methods: ["get","head"],
    url: '/admin/tournaments/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AdminController::createTournament
 * @see app/Http/Controllers/AdminController.php:335
 * @route '/admin/tournaments/create'
 */
createTournament.url = (options?: RouteQueryOptions) => {
    return createTournament.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::createTournament
 * @see app/Http/Controllers/AdminController.php:335
 * @route '/admin/tournaments/create'
 */
createTournament.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: createTournament.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AdminController::createTournament
 * @see app/Http/Controllers/AdminController.php:335
 * @route '/admin/tournaments/create'
 */
createTournament.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: createTournament.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\AdminController::createTournament
 * @see app/Http/Controllers/AdminController.php:335
 * @route '/admin/tournaments/create'
 */
    const createTournamentForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: createTournament.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\AdminController::createTournament
 * @see app/Http/Controllers/AdminController.php:335
 * @route '/admin/tournaments/create'
 */
        createTournamentForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: createTournament.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\AdminController::createTournament
 * @see app/Http/Controllers/AdminController.php:335
 * @route '/admin/tournaments/create'
 */
        createTournamentForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: createTournament.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    createTournament.form = createTournamentForm
/**
* @see \App\Http\Controllers\AdminController::storeTournament
 * @see app/Http/Controllers/AdminController.php:345
 * @route '/admin/tournaments'
 */
export const storeTournament = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeTournament.url(options),
    method: 'post',
})

storeTournament.definition = {
    methods: ["post"],
    url: '/admin/tournaments',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AdminController::storeTournament
 * @see app/Http/Controllers/AdminController.php:345
 * @route '/admin/tournaments'
 */
storeTournament.url = (options?: RouteQueryOptions) => {
    return storeTournament.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::storeTournament
 * @see app/Http/Controllers/AdminController.php:345
 * @route '/admin/tournaments'
 */
storeTournament.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeTournament.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\AdminController::storeTournament
 * @see app/Http/Controllers/AdminController.php:345
 * @route '/admin/tournaments'
 */
    const storeTournamentForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: storeTournament.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\AdminController::storeTournament
 * @see app/Http/Controllers/AdminController.php:345
 * @route '/admin/tournaments'
 */
        storeTournamentForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: storeTournament.url(options),
            method: 'post',
        })
    
    storeTournament.form = storeTournamentForm
/**
* @see \App\Http\Controllers\AdminController::viewTournament
 * @see app/Http/Controllers/AdminController.php:849
 * @route '/admin/tournaments/{tournament}'
 */
export const viewTournament = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: viewTournament.url(args, options),
    method: 'get',
})

viewTournament.definition = {
    methods: ["get","head"],
    url: '/admin/tournaments/{tournament}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AdminController::viewTournament
 * @see app/Http/Controllers/AdminController.php:849
 * @route '/admin/tournaments/{tournament}'
 */
viewTournament.url = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return viewTournament.definition.url
            .replace('{tournament}', parsedArgs.tournament.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::viewTournament
 * @see app/Http/Controllers/AdminController.php:849
 * @route '/admin/tournaments/{tournament}'
 */
viewTournament.get = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: viewTournament.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AdminController::viewTournament
 * @see app/Http/Controllers/AdminController.php:849
 * @route '/admin/tournaments/{tournament}'
 */
viewTournament.head = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: viewTournament.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\AdminController::viewTournament
 * @see app/Http/Controllers/AdminController.php:849
 * @route '/admin/tournaments/{tournament}'
 */
    const viewTournamentForm = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: viewTournament.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\AdminController::viewTournament
 * @see app/Http/Controllers/AdminController.php:849
 * @route '/admin/tournaments/{tournament}'
 */
        viewTournamentForm.get = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: viewTournament.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\AdminController::viewTournament
 * @see app/Http/Controllers/AdminController.php:849
 * @route '/admin/tournaments/{tournament}'
 */
        viewTournamentForm.head = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: viewTournament.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    viewTournament.form = viewTournamentForm
/**
* @see \App\Http\Controllers\AdminController::editTournament
 * @see app/Http/Controllers/AdminController.php:406
 * @route '/admin/tournaments/{tournament}/edit'
 */
export const editTournament = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: editTournament.url(args, options),
    method: 'get',
})

editTournament.definition = {
    methods: ["get","head"],
    url: '/admin/tournaments/{tournament}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AdminController::editTournament
 * @see app/Http/Controllers/AdminController.php:406
 * @route '/admin/tournaments/{tournament}/edit'
 */
editTournament.url = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return editTournament.definition.url
            .replace('{tournament}', parsedArgs.tournament.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::editTournament
 * @see app/Http/Controllers/AdminController.php:406
 * @route '/admin/tournaments/{tournament}/edit'
 */
editTournament.get = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: editTournament.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AdminController::editTournament
 * @see app/Http/Controllers/AdminController.php:406
 * @route '/admin/tournaments/{tournament}/edit'
 */
editTournament.head = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: editTournament.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\AdminController::editTournament
 * @see app/Http/Controllers/AdminController.php:406
 * @route '/admin/tournaments/{tournament}/edit'
 */
    const editTournamentForm = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: editTournament.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\AdminController::editTournament
 * @see app/Http/Controllers/AdminController.php:406
 * @route '/admin/tournaments/{tournament}/edit'
 */
        editTournamentForm.get = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: editTournament.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\AdminController::editTournament
 * @see app/Http/Controllers/AdminController.php:406
 * @route '/admin/tournaments/{tournament}/edit'
 */
        editTournamentForm.head = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: editTournament.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    editTournament.form = editTournamentForm
/**
* @see \App\Http\Controllers\AdminController::updateTournament
 * @see app/Http/Controllers/AdminController.php:416
 * @route '/admin/tournaments/{tournament}'
 */
export const updateTournament = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateTournament.url(args, options),
    method: 'put',
})

updateTournament.definition = {
    methods: ["put"],
    url: '/admin/tournaments/{tournament}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\AdminController::updateTournament
 * @see app/Http/Controllers/AdminController.php:416
 * @route '/admin/tournaments/{tournament}'
 */
updateTournament.url = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return updateTournament.definition.url
            .replace('{tournament}', parsedArgs.tournament.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::updateTournament
 * @see app/Http/Controllers/AdminController.php:416
 * @route '/admin/tournaments/{tournament}'
 */
updateTournament.put = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateTournament.url(args, options),
    method: 'put',
})

    /**
* @see \App\Http\Controllers\AdminController::updateTournament
 * @see app/Http/Controllers/AdminController.php:416
 * @route '/admin/tournaments/{tournament}'
 */
    const updateTournamentForm = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: updateTournament.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'PUT',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\AdminController::updateTournament
 * @see app/Http/Controllers/AdminController.php:416
 * @route '/admin/tournaments/{tournament}'
 */
        updateTournamentForm.put = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: updateTournament.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'PUT',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    updateTournament.form = updateTournamentForm
/**
* @see \App\Http\Controllers\AdminController::deleteTournament
 * @see app/Http/Controllers/AdminController.php:476
 * @route '/admin/tournaments/{tournament}'
 */
export const deleteTournament = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: deleteTournament.url(args, options),
    method: 'delete',
})

deleteTournament.definition = {
    methods: ["delete"],
    url: '/admin/tournaments/{tournament}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\AdminController::deleteTournament
 * @see app/Http/Controllers/AdminController.php:476
 * @route '/admin/tournaments/{tournament}'
 */
deleteTournament.url = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return deleteTournament.definition.url
            .replace('{tournament}', parsedArgs.tournament.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::deleteTournament
 * @see app/Http/Controllers/AdminController.php:476
 * @route '/admin/tournaments/{tournament}'
 */
deleteTournament.delete = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: deleteTournament.url(args, options),
    method: 'delete',
})

    /**
* @see \App\Http\Controllers\AdminController::deleteTournament
 * @see app/Http/Controllers/AdminController.php:476
 * @route '/admin/tournaments/{tournament}'
 */
    const deleteTournamentForm = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: deleteTournament.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'DELETE',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\AdminController::deleteTournament
 * @see app/Http/Controllers/AdminController.php:476
 * @route '/admin/tournaments/{tournament}'
 */
        deleteTournamentForm.delete = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: deleteTournament.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'DELETE',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    deleteTournament.form = deleteTournamentForm
/**
* @see \App\Http\Controllers\AdminController::initializeTournamentLevel
 * @see app/Http/Controllers/AdminController.php:1053
 * @route '/admin/tournaments/{tournament}/initialize/{level}'
 */
export const initializeTournamentLevel = (args: { tournament: number | { id: number }, level: string | number } | [tournament: number | { id: number }, level: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: initializeTournamentLevel.url(args, options),
    method: 'post',
})

initializeTournamentLevel.definition = {
    methods: ["post"],
    url: '/admin/tournaments/{tournament}/initialize/{level}',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AdminController::initializeTournamentLevel
 * @see app/Http/Controllers/AdminController.php:1053
 * @route '/admin/tournaments/{tournament}/initialize/{level}'
 */
initializeTournamentLevel.url = (args: { tournament: number | { id: number }, level: string | number } | [tournament: number | { id: number }, level: string | number ], options?: RouteQueryOptions) => {
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

    return initializeTournamentLevel.definition.url
            .replace('{tournament}', parsedArgs.tournament.toString())
            .replace('{level}', parsedArgs.level.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::initializeTournamentLevel
 * @see app/Http/Controllers/AdminController.php:1053
 * @route '/admin/tournaments/{tournament}/initialize/{level}'
 */
initializeTournamentLevel.post = (args: { tournament: number | { id: number }, level: string | number } | [tournament: number | { id: number }, level: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: initializeTournamentLevel.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\AdminController::initializeTournamentLevel
 * @see app/Http/Controllers/AdminController.php:1053
 * @route '/admin/tournaments/{tournament}/initialize/{level}'
 */
    const initializeTournamentLevelForm = (args: { tournament: number | { id: number }, level: string | number } | [tournament: number | { id: number }, level: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: initializeTournamentLevel.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\AdminController::initializeTournamentLevel
 * @see app/Http/Controllers/AdminController.php:1053
 * @route '/admin/tournaments/{tournament}/initialize/{level}'
 */
        initializeTournamentLevelForm.post = (args: { tournament: number | { id: number }, level: string | number } | [tournament: number | { id: number }, level: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: initializeTournamentLevel.url(args, options),
            method: 'post',
        })
    
    initializeTournamentLevel.form = initializeTournamentLevelForm
/**
* @see \App\Http\Controllers\AdminController::communities
 * @see app/Http/Controllers/AdminController.php:281
 * @route '/admin/communities'
 */
export const communities = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: communities.url(options),
    method: 'get',
})

communities.definition = {
    methods: ["get","head"],
    url: '/admin/communities',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AdminController::communities
 * @see app/Http/Controllers/AdminController.php:281
 * @route '/admin/communities'
 */
communities.url = (options?: RouteQueryOptions) => {
    return communities.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::communities
 * @see app/Http/Controllers/AdminController.php:281
 * @route '/admin/communities'
 */
communities.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: communities.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AdminController::communities
 * @see app/Http/Controllers/AdminController.php:281
 * @route '/admin/communities'
 */
communities.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: communities.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\AdminController::communities
 * @see app/Http/Controllers/AdminController.php:281
 * @route '/admin/communities'
 */
    const communitiesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: communities.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\AdminController::communities
 * @see app/Http/Controllers/AdminController.php:281
 * @route '/admin/communities'
 */
        communitiesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: communities.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\AdminController::communities
 * @see app/Http/Controllers/AdminController.php:281
 * @route '/admin/communities'
 */
        communitiesForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: communities.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    communities.form = communitiesForm
/**
* @see \App\Http\Controllers\AdminController::createCommunity
 * @see app/Http/Controllers/AdminController.php:568
 * @route '/admin/communities/create'
 */
export const createCommunity = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: createCommunity.url(options),
    method: 'get',
})

createCommunity.definition = {
    methods: ["get","head"],
    url: '/admin/communities/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AdminController::createCommunity
 * @see app/Http/Controllers/AdminController.php:568
 * @route '/admin/communities/create'
 */
createCommunity.url = (options?: RouteQueryOptions) => {
    return createCommunity.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::createCommunity
 * @see app/Http/Controllers/AdminController.php:568
 * @route '/admin/communities/create'
 */
createCommunity.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: createCommunity.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AdminController::createCommunity
 * @see app/Http/Controllers/AdminController.php:568
 * @route '/admin/communities/create'
 */
createCommunity.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: createCommunity.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\AdminController::createCommunity
 * @see app/Http/Controllers/AdminController.php:568
 * @route '/admin/communities/create'
 */
    const createCommunityForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: createCommunity.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\AdminController::createCommunity
 * @see app/Http/Controllers/AdminController.php:568
 * @route '/admin/communities/create'
 */
        createCommunityForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: createCommunity.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\AdminController::createCommunity
 * @see app/Http/Controllers/AdminController.php:568
 * @route '/admin/communities/create'
 */
        createCommunityForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: createCommunity.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    createCommunity.form = createCommunityForm
/**
* @see \App\Http\Controllers\AdminController::storeCommunity
 * @see app/Http/Controllers/AdminController.php:580
 * @route '/admin/communities'
 */
export const storeCommunity = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeCommunity.url(options),
    method: 'post',
})

storeCommunity.definition = {
    methods: ["post"],
    url: '/admin/communities',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AdminController::storeCommunity
 * @see app/Http/Controllers/AdminController.php:580
 * @route '/admin/communities'
 */
storeCommunity.url = (options?: RouteQueryOptions) => {
    return storeCommunity.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::storeCommunity
 * @see app/Http/Controllers/AdminController.php:580
 * @route '/admin/communities'
 */
storeCommunity.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeCommunity.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\AdminController::storeCommunity
 * @see app/Http/Controllers/AdminController.php:580
 * @route '/admin/communities'
 */
    const storeCommunityForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: storeCommunity.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\AdminController::storeCommunity
 * @see app/Http/Controllers/AdminController.php:580
 * @route '/admin/communities'
 */
        storeCommunityForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: storeCommunity.url(options),
            method: 'post',
        })
    
    storeCommunity.form = storeCommunityForm
/**
* @see \App\Http\Controllers\AdminController::editCommunity
 * @see app/Http/Controllers/AdminController.php:601
 * @route '/admin/communities/{community}/edit'
 */
export const editCommunity = (args: { community: number | { id: number } } | [community: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: editCommunity.url(args, options),
    method: 'get',
})

editCommunity.definition = {
    methods: ["get","head"],
    url: '/admin/communities/{community}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AdminController::editCommunity
 * @see app/Http/Controllers/AdminController.php:601
 * @route '/admin/communities/{community}/edit'
 */
editCommunity.url = (args: { community: number | { id: number } } | [community: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return editCommunity.definition.url
            .replace('{community}', parsedArgs.community.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::editCommunity
 * @see app/Http/Controllers/AdminController.php:601
 * @route '/admin/communities/{community}/edit'
 */
editCommunity.get = (args: { community: number | { id: number } } | [community: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: editCommunity.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AdminController::editCommunity
 * @see app/Http/Controllers/AdminController.php:601
 * @route '/admin/communities/{community}/edit'
 */
editCommunity.head = (args: { community: number | { id: number } } | [community: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: editCommunity.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\AdminController::editCommunity
 * @see app/Http/Controllers/AdminController.php:601
 * @route '/admin/communities/{community}/edit'
 */
    const editCommunityForm = (args: { community: number | { id: number } } | [community: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: editCommunity.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\AdminController::editCommunity
 * @see app/Http/Controllers/AdminController.php:601
 * @route '/admin/communities/{community}/edit'
 */
        editCommunityForm.get = (args: { community: number | { id: number } } | [community: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: editCommunity.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\AdminController::editCommunity
 * @see app/Http/Controllers/AdminController.php:601
 * @route '/admin/communities/{community}/edit'
 */
        editCommunityForm.head = (args: { community: number | { id: number } } | [community: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: editCommunity.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    editCommunity.form = editCommunityForm
/**
* @see \App\Http\Controllers\AdminController::updateCommunity
 * @see app/Http/Controllers/AdminController.php:613
 * @route '/admin/communities/{community}'
 */
export const updateCommunity = (args: { community: number | { id: number } } | [community: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateCommunity.url(args, options),
    method: 'put',
})

updateCommunity.definition = {
    methods: ["put"],
    url: '/admin/communities/{community}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\AdminController::updateCommunity
 * @see app/Http/Controllers/AdminController.php:613
 * @route '/admin/communities/{community}'
 */
updateCommunity.url = (args: { community: number | { id: number } } | [community: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return updateCommunity.definition.url
            .replace('{community}', parsedArgs.community.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::updateCommunity
 * @see app/Http/Controllers/AdminController.php:613
 * @route '/admin/communities/{community}'
 */
updateCommunity.put = (args: { community: number | { id: number } } | [community: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateCommunity.url(args, options),
    method: 'put',
})

    /**
* @see \App\Http\Controllers\AdminController::updateCommunity
 * @see app/Http/Controllers/AdminController.php:613
 * @route '/admin/communities/{community}'
 */
    const updateCommunityForm = (args: { community: number | { id: number } } | [community: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: updateCommunity.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'PUT',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\AdminController::updateCommunity
 * @see app/Http/Controllers/AdminController.php:613
 * @route '/admin/communities/{community}'
 */
        updateCommunityForm.put = (args: { community: number | { id: number } } | [community: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: updateCommunity.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'PUT',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    updateCommunity.form = updateCommunityForm
/**
* @see \App\Http\Controllers\AdminController::deleteCommunity
 * @see app/Http/Controllers/AdminController.php:634
 * @route '/admin/communities/{community}'
 */
export const deleteCommunity = (args: { community: number | { id: number } } | [community: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: deleteCommunity.url(args, options),
    method: 'delete',
})

deleteCommunity.definition = {
    methods: ["delete"],
    url: '/admin/communities/{community}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\AdminController::deleteCommunity
 * @see app/Http/Controllers/AdminController.php:634
 * @route '/admin/communities/{community}'
 */
deleteCommunity.url = (args: { community: number | { id: number } } | [community: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return deleteCommunity.definition.url
            .replace('{community}', parsedArgs.community.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::deleteCommunity
 * @see app/Http/Controllers/AdminController.php:634
 * @route '/admin/communities/{community}'
 */
deleteCommunity.delete = (args: { community: number | { id: number } } | [community: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: deleteCommunity.url(args, options),
    method: 'delete',
})

    /**
* @see \App\Http\Controllers\AdminController::deleteCommunity
 * @see app/Http/Controllers/AdminController.php:634
 * @route '/admin/communities/{community}'
 */
    const deleteCommunityForm = (args: { community: number | { id: number } } | [community: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: deleteCommunity.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'DELETE',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\AdminController::deleteCommunity
 * @see app/Http/Controllers/AdminController.php:634
 * @route '/admin/communities/{community}'
 */
        deleteCommunityForm.delete = (args: { community: number | { id: number } } | [community: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: deleteCommunity.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'DELETE',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    deleteCommunity.form = deleteCommunityForm
/**
* @see \App\Http\Controllers\AdminController::viewCommunity
 * @see app/Http/Controllers/AdminController.php:491
 * @route '/admin/communities/{community}'
 */
export const viewCommunity = (args: { community: number | { id: number } } | [community: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: viewCommunity.url(args, options),
    method: 'get',
})

viewCommunity.definition = {
    methods: ["get","head"],
    url: '/admin/communities/{community}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AdminController::viewCommunity
 * @see app/Http/Controllers/AdminController.php:491
 * @route '/admin/communities/{community}'
 */
viewCommunity.url = (args: { community: number | { id: number } } | [community: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return viewCommunity.definition.url
            .replace('{community}', parsedArgs.community.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::viewCommunity
 * @see app/Http/Controllers/AdminController.php:491
 * @route '/admin/communities/{community}'
 */
viewCommunity.get = (args: { community: number | { id: number } } | [community: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: viewCommunity.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AdminController::viewCommunity
 * @see app/Http/Controllers/AdminController.php:491
 * @route '/admin/communities/{community}'
 */
viewCommunity.head = (args: { community: number | { id: number } } | [community: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: viewCommunity.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\AdminController::viewCommunity
 * @see app/Http/Controllers/AdminController.php:491
 * @route '/admin/communities/{community}'
 */
    const viewCommunityForm = (args: { community: number | { id: number } } | [community: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: viewCommunity.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\AdminController::viewCommunity
 * @see app/Http/Controllers/AdminController.php:491
 * @route '/admin/communities/{community}'
 */
        viewCommunityForm.get = (args: { community: number | { id: number } } | [community: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: viewCommunity.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\AdminController::viewCommunity
 * @see app/Http/Controllers/AdminController.php:491
 * @route '/admin/communities/{community}'
 */
        viewCommunityForm.head = (args: { community: number | { id: number } } | [community: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: viewCommunity.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    viewCommunity.form = viewCommunityForm
/**
* @see \App\Http\Controllers\AdminController::matches
 * @see app/Http/Controllers/AdminController.php:137
 * @route '/admin/matches'
 */
export const matches = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: matches.url(options),
    method: 'get',
})

matches.definition = {
    methods: ["get","head"],
    url: '/admin/matches',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AdminController::matches
 * @see app/Http/Controllers/AdminController.php:137
 * @route '/admin/matches'
 */
matches.url = (options?: RouteQueryOptions) => {
    return matches.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::matches
 * @see app/Http/Controllers/AdminController.php:137
 * @route '/admin/matches'
 */
matches.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: matches.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AdminController::matches
 * @see app/Http/Controllers/AdminController.php:137
 * @route '/admin/matches'
 */
matches.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: matches.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\AdminController::matches
 * @see app/Http/Controllers/AdminController.php:137
 * @route '/admin/matches'
 */
    const matchesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: matches.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\AdminController::matches
 * @see app/Http/Controllers/AdminController.php:137
 * @route '/admin/matches'
 */
        matchesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: matches.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\AdminController::matches
 * @see app/Http/Controllers/AdminController.php:137
 * @route '/admin/matches'
 */
        matchesForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: matches.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    matches.form = matchesForm
/**
* @see \App\Http\Controllers\AdminController::deleteMatch
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/matches/{match}'
 */
export const deleteMatch = (args: { match: string | number } | [match: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: deleteMatch.url(args, options),
    method: 'delete',
})

deleteMatch.definition = {
    methods: ["delete"],
    url: '/admin/matches/{match}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\AdminController::deleteMatch
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/matches/{match}'
 */
deleteMatch.url = (args: { match: string | number } | [match: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { match: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    match: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        match: args.match,
                }

    return deleteMatch.definition.url
            .replace('{match}', parsedArgs.match.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::deleteMatch
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/matches/{match}'
 */
deleteMatch.delete = (args: { match: string | number } | [match: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: deleteMatch.url(args, options),
    method: 'delete',
})

    /**
* @see \App\Http\Controllers\AdminController::deleteMatch
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/matches/{match}'
 */
    const deleteMatchForm = (args: { match: string | number } | [match: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: deleteMatch.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'DELETE',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\AdminController::deleteMatch
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/matches/{match}'
 */
        deleteMatchForm.delete = (args: { match: string | number } | [match: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: deleteMatch.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'DELETE',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    deleteMatch.form = deleteMatchForm
/**
* @see \App\Http\Controllers\AdminController::players
 * @see app/Http/Controllers/AdminController.php:175
 * @route '/admin/players'
 */
export const players = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: players.url(options),
    method: 'get',
})

players.definition = {
    methods: ["get","head"],
    url: '/admin/players',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AdminController::players
 * @see app/Http/Controllers/AdminController.php:175
 * @route '/admin/players'
 */
players.url = (options?: RouteQueryOptions) => {
    return players.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::players
 * @see app/Http/Controllers/AdminController.php:175
 * @route '/admin/players'
 */
players.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: players.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AdminController::players
 * @see app/Http/Controllers/AdminController.php:175
 * @route '/admin/players'
 */
players.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: players.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\AdminController::players
 * @see app/Http/Controllers/AdminController.php:175
 * @route '/admin/players'
 */
    const playersForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: players.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\AdminController::players
 * @see app/Http/Controllers/AdminController.php:175
 * @route '/admin/players'
 */
        playersForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: players.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\AdminController::players
 * @see app/Http/Controllers/AdminController.php:175
 * @route '/admin/players'
 */
        playersForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: players.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    players.form = playersForm
/**
* @see \App\Http\Controllers\AdminController::viewPlayer
 * @see app/Http/Controllers/AdminController.php:1123
 * @route '/admin/players/{player}'
 */
export const viewPlayer = (args: { player: number | { id: number } } | [player: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: viewPlayer.url(args, options),
    method: 'get',
})

viewPlayer.definition = {
    methods: ["get","head"],
    url: '/admin/players/{player}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AdminController::viewPlayer
 * @see app/Http/Controllers/AdminController.php:1123
 * @route '/admin/players/{player}'
 */
viewPlayer.url = (args: { player: number | { id: number } } | [player: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return viewPlayer.definition.url
            .replace('{player}', parsedArgs.player.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::viewPlayer
 * @see app/Http/Controllers/AdminController.php:1123
 * @route '/admin/players/{player}'
 */
viewPlayer.get = (args: { player: number | { id: number } } | [player: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: viewPlayer.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AdminController::viewPlayer
 * @see app/Http/Controllers/AdminController.php:1123
 * @route '/admin/players/{player}'
 */
viewPlayer.head = (args: { player: number | { id: number } } | [player: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: viewPlayer.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\AdminController::viewPlayer
 * @see app/Http/Controllers/AdminController.php:1123
 * @route '/admin/players/{player}'
 */
    const viewPlayerForm = (args: { player: number | { id: number } } | [player: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: viewPlayer.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\AdminController::viewPlayer
 * @see app/Http/Controllers/AdminController.php:1123
 * @route '/admin/players/{player}'
 */
        viewPlayerForm.get = (args: { player: number | { id: number } } | [player: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: viewPlayer.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\AdminController::viewPlayer
 * @see app/Http/Controllers/AdminController.php:1123
 * @route '/admin/players/{player}'
 */
        viewPlayerForm.head = (args: { player: number | { id: number } } | [player: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: viewPlayer.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    viewPlayer.form = viewPlayerForm
/**
* @see \App\Http\Controllers\AdminController::deletePlayer
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/players/{player}'
 */
export const deletePlayer = (args: { player: string | number } | [player: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: deletePlayer.url(args, options),
    method: 'delete',
})

deletePlayer.definition = {
    methods: ["delete"],
    url: '/admin/players/{player}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\AdminController::deletePlayer
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/players/{player}'
 */
deletePlayer.url = (args: { player: string | number } | [player: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return deletePlayer.definition.url
            .replace('{player}', parsedArgs.player.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::deletePlayer
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/players/{player}'
 */
deletePlayer.delete = (args: { player: string | number } | [player: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: deletePlayer.url(args, options),
    method: 'delete',
})

    /**
* @see \App\Http\Controllers\AdminController::deletePlayer
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/players/{player}'
 */
    const deletePlayerForm = (args: { player: string | number } | [player: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: deletePlayer.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'DELETE',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\AdminController::deletePlayer
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/players/{player}'
 */
        deletePlayerForm.delete = (args: { player: string | number } | [player: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: deletePlayer.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'DELETE',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    deletePlayer.form = deletePlayerForm
/**
* @see \App\Http\Controllers\AdminController::messages
 * @see app/Http/Controllers/AdminController.php:243
 * @route '/admin/messages'
 */
export const messages = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: messages.url(options),
    method: 'get',
})

messages.definition = {
    methods: ["get","head"],
    url: '/admin/messages',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AdminController::messages
 * @see app/Http/Controllers/AdminController.php:243
 * @route '/admin/messages'
 */
messages.url = (options?: RouteQueryOptions) => {
    return messages.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::messages
 * @see app/Http/Controllers/AdminController.php:243
 * @route '/admin/messages'
 */
messages.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: messages.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AdminController::messages
 * @see app/Http/Controllers/AdminController.php:243
 * @route '/admin/messages'
 */
messages.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: messages.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\AdminController::messages
 * @see app/Http/Controllers/AdminController.php:243
 * @route '/admin/messages'
 */
    const messagesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: messages.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\AdminController::messages
 * @see app/Http/Controllers/AdminController.php:243
 * @route '/admin/messages'
 */
        messagesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: messages.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\AdminController::messages
 * @see app/Http/Controllers/AdminController.php:243
 * @route '/admin/messages'
 */
        messagesForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: messages.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    messages.form = messagesForm
/**
* @see \App\Http\Controllers\AdminController::winners
 * @see app/Http/Controllers/AdminController.php:714
 * @route '/admin/winners'
 */
export const winners = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: winners.url(options),
    method: 'get',
})

winners.definition = {
    methods: ["get","head"],
    url: '/admin/winners',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AdminController::winners
 * @see app/Http/Controllers/AdminController.php:714
 * @route '/admin/winners'
 */
winners.url = (options?: RouteQueryOptions) => {
    return winners.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::winners
 * @see app/Http/Controllers/AdminController.php:714
 * @route '/admin/winners'
 */
winners.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: winners.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AdminController::winners
 * @see app/Http/Controllers/AdminController.php:714
 * @route '/admin/winners'
 */
winners.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: winners.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\AdminController::winners
 * @see app/Http/Controllers/AdminController.php:714
 * @route '/admin/winners'
 */
    const winnersForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: winners.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\AdminController::winners
 * @see app/Http/Controllers/AdminController.php:714
 * @route '/admin/winners'
 */
        winnersForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: winners.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\AdminController::winners
 * @see app/Http/Controllers/AdminController.php:714
 * @route '/admin/winners'
 */
        winnersForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: winners.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    winners.form = winnersForm
/**
* @see \App\Http\Controllers\AdminController::createWinner
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/winners/create'
 */
export const createWinner = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: createWinner.url(options),
    method: 'get',
})

createWinner.definition = {
    methods: ["get","head"],
    url: '/admin/winners/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AdminController::createWinner
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/winners/create'
 */
createWinner.url = (options?: RouteQueryOptions) => {
    return createWinner.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::createWinner
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/winners/create'
 */
createWinner.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: createWinner.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AdminController::createWinner
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/winners/create'
 */
createWinner.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: createWinner.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\AdminController::createWinner
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/winners/create'
 */
    const createWinnerForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: createWinner.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\AdminController::createWinner
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/winners/create'
 */
        createWinnerForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: createWinner.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\AdminController::createWinner
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/winners/create'
 */
        createWinnerForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: createWinner.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    createWinner.form = createWinnerForm
/**
* @see \App\Http\Controllers\AdminController::storeWinner
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/winners'
 */
export const storeWinner = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeWinner.url(options),
    method: 'post',
})

storeWinner.definition = {
    methods: ["post"],
    url: '/admin/winners',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AdminController::storeWinner
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/winners'
 */
storeWinner.url = (options?: RouteQueryOptions) => {
    return storeWinner.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::storeWinner
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/winners'
 */
storeWinner.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeWinner.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\AdminController::storeWinner
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/winners'
 */
    const storeWinnerForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: storeWinner.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\AdminController::storeWinner
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/winners'
 */
        storeWinnerForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: storeWinner.url(options),
            method: 'post',
        })
    
    storeWinner.form = storeWinnerForm
/**
* @see \App\Http\Controllers\AdminController::editWinner
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/winners/{winner}/edit'
 */
export const editWinner = (args: { winner: string | number } | [winner: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: editWinner.url(args, options),
    method: 'get',
})

editWinner.definition = {
    methods: ["get","head"],
    url: '/admin/winners/{winner}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AdminController::editWinner
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/winners/{winner}/edit'
 */
editWinner.url = (args: { winner: string | number } | [winner: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { winner: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    winner: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        winner: args.winner,
                }

    return editWinner.definition.url
            .replace('{winner}', parsedArgs.winner.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::editWinner
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/winners/{winner}/edit'
 */
editWinner.get = (args: { winner: string | number } | [winner: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: editWinner.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AdminController::editWinner
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/winners/{winner}/edit'
 */
editWinner.head = (args: { winner: string | number } | [winner: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: editWinner.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\AdminController::editWinner
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/winners/{winner}/edit'
 */
    const editWinnerForm = (args: { winner: string | number } | [winner: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: editWinner.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\AdminController::editWinner
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/winners/{winner}/edit'
 */
        editWinnerForm.get = (args: { winner: string | number } | [winner: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: editWinner.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\AdminController::editWinner
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/winners/{winner}/edit'
 */
        editWinnerForm.head = (args: { winner: string | number } | [winner: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: editWinner.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    editWinner.form = editWinnerForm
/**
* @see \App\Http\Controllers\AdminController::updateWinner
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/winners/{winner}'
 */
export const updateWinner = (args: { winner: string | number } | [winner: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateWinner.url(args, options),
    method: 'put',
})

updateWinner.definition = {
    methods: ["put"],
    url: '/admin/winners/{winner}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\AdminController::updateWinner
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/winners/{winner}'
 */
updateWinner.url = (args: { winner: string | number } | [winner: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { winner: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    winner: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        winner: args.winner,
                }

    return updateWinner.definition.url
            .replace('{winner}', parsedArgs.winner.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::updateWinner
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/winners/{winner}'
 */
updateWinner.put = (args: { winner: string | number } | [winner: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateWinner.url(args, options),
    method: 'put',
})

    /**
* @see \App\Http\Controllers\AdminController::updateWinner
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/winners/{winner}'
 */
    const updateWinnerForm = (args: { winner: string | number } | [winner: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: updateWinner.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'PUT',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\AdminController::updateWinner
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/winners/{winner}'
 */
        updateWinnerForm.put = (args: { winner: string | number } | [winner: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: updateWinner.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'PUT',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    updateWinner.form = updateWinnerForm
/**
* @see \App\Http\Controllers\AdminController::deleteWinner
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/winners/{winner}'
 */
export const deleteWinner = (args: { winner: string | number } | [winner: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: deleteWinner.url(args, options),
    method: 'delete',
})

deleteWinner.definition = {
    methods: ["delete"],
    url: '/admin/winners/{winner}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\AdminController::deleteWinner
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/winners/{winner}'
 */
deleteWinner.url = (args: { winner: string | number } | [winner: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { winner: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    winner: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        winner: args.winner,
                }

    return deleteWinner.definition.url
            .replace('{winner}', parsedArgs.winner.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::deleteWinner
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/winners/{winner}'
 */
deleteWinner.delete = (args: { winner: string | number } | [winner: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: deleteWinner.url(args, options),
    method: 'delete',
})

    /**
* @see \App\Http\Controllers\AdminController::deleteWinner
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/winners/{winner}'
 */
    const deleteWinnerForm = (args: { winner: string | number } | [winner: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: deleteWinner.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'DELETE',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\AdminController::deleteWinner
 * @see app/Http/Controllers/AdminController.php:0
 * @route '/admin/winners/{winner}'
 */
        deleteWinnerForm.delete = (args: { winner: string | number } | [winner: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: deleteWinner.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'DELETE',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    deleteWinner.form = deleteWinnerForm
/**
* @see \App\Http\Controllers\AdminController::transactions
 * @see app/Http/Controllers/AdminController.php:649
 * @route '/admin/transactions'
 */
export const transactions = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: transactions.url(options),
    method: 'get',
})

transactions.definition = {
    methods: ["get","head"],
    url: '/admin/transactions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AdminController::transactions
 * @see app/Http/Controllers/AdminController.php:649
 * @route '/admin/transactions'
 */
transactions.url = (options?: RouteQueryOptions) => {
    return transactions.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::transactions
 * @see app/Http/Controllers/AdminController.php:649
 * @route '/admin/transactions'
 */
transactions.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: transactions.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AdminController::transactions
 * @see app/Http/Controllers/AdminController.php:649
 * @route '/admin/transactions'
 */
transactions.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: transactions.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\AdminController::transactions
 * @see app/Http/Controllers/AdminController.php:649
 * @route '/admin/transactions'
 */
    const transactionsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: transactions.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\AdminController::transactions
 * @see app/Http/Controllers/AdminController.php:649
 * @route '/admin/transactions'
 */
        transactionsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: transactions.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\AdminController::transactions
 * @see app/Http/Controllers/AdminController.php:649
 * @route '/admin/transactions'
 */
        transactionsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: transactions.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    transactions.form = transactionsForm
/**
* @see \App\Http\Controllers\AdminController::showTransaction
 * @see app/Http/Controllers/AdminController.php:696
 * @route '/admin/transactions/{transaction}'
 */
export const showTransaction = (args: { transaction: string | number } | [transaction: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showTransaction.url(args, options),
    method: 'get',
})

showTransaction.definition = {
    methods: ["get","head"],
    url: '/admin/transactions/{transaction}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AdminController::showTransaction
 * @see app/Http/Controllers/AdminController.php:696
 * @route '/admin/transactions/{transaction}'
 */
showTransaction.url = (args: { transaction: string | number } | [transaction: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { transaction: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    transaction: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        transaction: args.transaction,
                }

    return showTransaction.definition.url
            .replace('{transaction}', parsedArgs.transaction.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::showTransaction
 * @see app/Http/Controllers/AdminController.php:696
 * @route '/admin/transactions/{transaction}'
 */
showTransaction.get = (args: { transaction: string | number } | [transaction: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showTransaction.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AdminController::showTransaction
 * @see app/Http/Controllers/AdminController.php:696
 * @route '/admin/transactions/{transaction}'
 */
showTransaction.head = (args: { transaction: string | number } | [transaction: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: showTransaction.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\AdminController::showTransaction
 * @see app/Http/Controllers/AdminController.php:696
 * @route '/admin/transactions/{transaction}'
 */
    const showTransactionForm = (args: { transaction: string | number } | [transaction: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: showTransaction.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\AdminController::showTransaction
 * @see app/Http/Controllers/AdminController.php:696
 * @route '/admin/transactions/{transaction}'
 */
        showTransactionForm.get = (args: { transaction: string | number } | [transaction: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: showTransaction.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\AdminController::showTransaction
 * @see app/Http/Controllers/AdminController.php:696
 * @route '/admin/transactions/{transaction}'
 */
        showTransactionForm.head = (args: { transaction: string | number } | [transaction: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: showTransaction.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    showTransaction.form = showTransactionForm
/**
* @see \App\Http\Controllers\AdminController::updateTransactionStatus
 * @see app/Http/Controllers/AdminController.php:702
 * @route '/admin/transactions/{transaction}/status'
 */
export const updateTransactionStatus = (args: { transaction: string | number } | [transaction: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: updateTransactionStatus.url(args, options),
    method: 'post',
})

updateTransactionStatus.definition = {
    methods: ["post"],
    url: '/admin/transactions/{transaction}/status',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AdminController::updateTransactionStatus
 * @see app/Http/Controllers/AdminController.php:702
 * @route '/admin/transactions/{transaction}/status'
 */
updateTransactionStatus.url = (args: { transaction: string | number } | [transaction: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { transaction: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    transaction: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        transaction: args.transaction,
                }

    return updateTransactionStatus.definition.url
            .replace('{transaction}', parsedArgs.transaction.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::updateTransactionStatus
 * @see app/Http/Controllers/AdminController.php:702
 * @route '/admin/transactions/{transaction}/status'
 */
updateTransactionStatus.post = (args: { transaction: string | number } | [transaction: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: updateTransactionStatus.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\AdminController::updateTransactionStatus
 * @see app/Http/Controllers/AdminController.php:702
 * @route '/admin/transactions/{transaction}/status'
 */
    const updateTransactionStatusForm = (args: { transaction: string | number } | [transaction: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: updateTransactionStatus.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\AdminController::updateTransactionStatus
 * @see app/Http/Controllers/AdminController.php:702
 * @route '/admin/transactions/{transaction}/status'
 */
        updateTransactionStatusForm.post = (args: { transaction: string | number } | [transaction: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: updateTransactionStatus.url(args, options),
            method: 'post',
        })
    
    updateTransactionStatus.form = updateTransactionStatusForm
/**
* @see \App\Http\Controllers\AdminController::logout
 * @see app/Http/Controllers/AdminController.php:840
 * @route '/admin/logout'
 */
export const logout = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: logout.url(options),
    method: 'post',
})

logout.definition = {
    methods: ["post"],
    url: '/admin/logout',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AdminController::logout
 * @see app/Http/Controllers/AdminController.php:840
 * @route '/admin/logout'
 */
logout.url = (options?: RouteQueryOptions) => {
    return logout.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::logout
 * @see app/Http/Controllers/AdminController.php:840
 * @route '/admin/logout'
 */
logout.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: logout.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\AdminController::logout
 * @see app/Http/Controllers/AdminController.php:840
 * @route '/admin/logout'
 */
    const logoutForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: logout.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\AdminController::logout
 * @see app/Http/Controllers/AdminController.php:840
 * @route '/admin/logout'
 */
        logoutForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: logout.url(options),
            method: 'post',
        })
    
    logout.form = logoutForm
/**
* @see \App\Http\Controllers\AdminController::getCountiesByRegion
 * @see app/Http/Controllers/AdminController.php:810
 * @route '/admin/api/counties/{region}'
 */
export const getCountiesByRegion = (args: { region: string | number } | [region: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getCountiesByRegion.url(args, options),
    method: 'get',
})

getCountiesByRegion.definition = {
    methods: ["get","head"],
    url: '/admin/api/counties/{region}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AdminController::getCountiesByRegion
 * @see app/Http/Controllers/AdminController.php:810
 * @route '/admin/api/counties/{region}'
 */
getCountiesByRegion.url = (args: { region: string | number } | [region: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return getCountiesByRegion.definition.url
            .replace('{region}', parsedArgs.region.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::getCountiesByRegion
 * @see app/Http/Controllers/AdminController.php:810
 * @route '/admin/api/counties/{region}'
 */
getCountiesByRegion.get = (args: { region: string | number } | [region: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getCountiesByRegion.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AdminController::getCountiesByRegion
 * @see app/Http/Controllers/AdminController.php:810
 * @route '/admin/api/counties/{region}'
 */
getCountiesByRegion.head = (args: { region: string | number } | [region: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: getCountiesByRegion.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\AdminController::getCountiesByRegion
 * @see app/Http/Controllers/AdminController.php:810
 * @route '/admin/api/counties/{region}'
 */
    const getCountiesByRegionForm = (args: { region: string | number } | [region: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: getCountiesByRegion.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\AdminController::getCountiesByRegion
 * @see app/Http/Controllers/AdminController.php:810
 * @route '/admin/api/counties/{region}'
 */
        getCountiesByRegionForm.get = (args: { region: string | number } | [region: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getCountiesByRegion.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\AdminController::getCountiesByRegion
 * @see app/Http/Controllers/AdminController.php:810
 * @route '/admin/api/counties/{region}'
 */
        getCountiesByRegionForm.head = (args: { region: string | number } | [region: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getCountiesByRegion.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    getCountiesByRegion.form = getCountiesByRegionForm
/**
* @see \App\Http\Controllers\AdminController::getCommunitiesByCounty
 * @see app/Http/Controllers/AdminController.php:825
 * @route '/admin/api/communities/{county}'
 */
export const getCommunitiesByCounty = (args: { county: string | number } | [county: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getCommunitiesByCounty.url(args, options),
    method: 'get',
})

getCommunitiesByCounty.definition = {
    methods: ["get","head"],
    url: '/admin/api/communities/{county}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AdminController::getCommunitiesByCounty
 * @see app/Http/Controllers/AdminController.php:825
 * @route '/admin/api/communities/{county}'
 */
getCommunitiesByCounty.url = (args: { county: string | number } | [county: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return getCommunitiesByCounty.definition.url
            .replace('{county}', parsedArgs.county.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminController::getCommunitiesByCounty
 * @see app/Http/Controllers/AdminController.php:825
 * @route '/admin/api/communities/{county}'
 */
getCommunitiesByCounty.get = (args: { county: string | number } | [county: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getCommunitiesByCounty.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AdminController::getCommunitiesByCounty
 * @see app/Http/Controllers/AdminController.php:825
 * @route '/admin/api/communities/{county}'
 */
getCommunitiesByCounty.head = (args: { county: string | number } | [county: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: getCommunitiesByCounty.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\AdminController::getCommunitiesByCounty
 * @see app/Http/Controllers/AdminController.php:825
 * @route '/admin/api/communities/{county}'
 */
    const getCommunitiesByCountyForm = (args: { county: string | number } | [county: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: getCommunitiesByCounty.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\AdminController::getCommunitiesByCounty
 * @see app/Http/Controllers/AdminController.php:825
 * @route '/admin/api/communities/{county}'
 */
        getCommunitiesByCountyForm.get = (args: { county: string | number } | [county: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getCommunitiesByCounty.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\AdminController::getCommunitiesByCounty
 * @see app/Http/Controllers/AdminController.php:825
 * @route '/admin/api/communities/{county}'
 */
        getCommunitiesByCountyForm.head = (args: { county: string | number } | [county: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getCommunitiesByCounty.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    getCommunitiesByCounty.form = getCommunitiesByCountyForm
const AdminController = { dashboard, tournaments, createTournament, storeTournament, viewTournament, editTournament, updateTournament, deleteTournament, initializeTournamentLevel, communities, createCommunity, storeCommunity, editCommunity, updateCommunity, deleteCommunity, viewCommunity, matches, deleteMatch, players, viewPlayer, deletePlayer, messages, winners, createWinner, storeWinner, editWinner, updateWinner, deleteWinner, transactions, showTransaction, updateTransactionStatus, logout, getCountiesByRegion, getCommunitiesByCounty }

export default AdminController