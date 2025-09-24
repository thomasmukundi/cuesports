import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Admin\TournamentController::initialize
 * @see app/Http/Controllers/Admin/TournamentController.php:154
 * @route '/api/admin/tournaments/{tournament}/initialize'
 */
export const initialize = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: initialize.url(args, options),
    method: 'post',
})

initialize.definition = {
    methods: ["post"],
    url: '/api/admin/tournaments/{tournament}/initialize',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Admin\TournamentController::initialize
 * @see app/Http/Controllers/Admin/TournamentController.php:154
 * @route '/api/admin/tournaments/{tournament}/initialize'
 */
initialize.url = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return initialize.definition.url
            .replace('{tournament}', parsedArgs.tournament.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\TournamentController::initialize
 * @see app/Http/Controllers/Admin/TournamentController.php:154
 * @route '/api/admin/tournaments/{tournament}/initialize'
 */
initialize.post = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: initialize.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Admin\TournamentController::initialize
 * @see app/Http/Controllers/Admin/TournamentController.php:154
 * @route '/api/admin/tournaments/{tournament}/initialize'
 */
    const initializeForm = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: initialize.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Admin\TournamentController::initialize
 * @see app/Http/Controllers/Admin/TournamentController.php:154
 * @route '/api/admin/tournaments/{tournament}/initialize'
 */
        initializeForm.post = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: initialize.url(args, options),
            method: 'post',
        })
    
    initialize.form = initializeForm
/**
* @see \App\Http\Controllers\Admin\TournamentController::generateNextRound
 * @see app/Http/Controllers/Admin/TournamentController.php:231
 * @route '/api/admin/tournaments/{tournament}/generate-next-round'
 */
export const generateNextRound = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: generateNextRound.url(args, options),
    method: 'post',
})

generateNextRound.definition = {
    methods: ["post"],
    url: '/api/admin/tournaments/{tournament}/generate-next-round',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Admin\TournamentController::generateNextRound
 * @see app/Http/Controllers/Admin/TournamentController.php:231
 * @route '/api/admin/tournaments/{tournament}/generate-next-round'
 */
generateNextRound.url = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { tournament: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    tournament: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        tournament: args.tournament,
                }

    return generateNextRound.definition.url
            .replace('{tournament}', parsedArgs.tournament.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\TournamentController::generateNextRound
 * @see app/Http/Controllers/Admin/TournamentController.php:231
 * @route '/api/admin/tournaments/{tournament}/generate-next-round'
 */
generateNextRound.post = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: generateNextRound.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Admin\TournamentController::generateNextRound
 * @see app/Http/Controllers/Admin/TournamentController.php:231
 * @route '/api/admin/tournaments/{tournament}/generate-next-round'
 */
    const generateNextRoundForm = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: generateNextRound.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Admin\TournamentController::generateNextRound
 * @see app/Http/Controllers/Admin/TournamentController.php:231
 * @route '/api/admin/tournaments/{tournament}/generate-next-round'
 */
        generateNextRoundForm.post = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: generateNextRound.url(args, options),
            method: 'post',
        })
    
    generateNextRound.form = generateNextRoundForm
/**
* @see \App\Http\Controllers\Admin\TournamentController::checkCompletion
 * @see app/Http/Controllers/Admin/TournamentController.php:256
 * @route '/api/admin/tournaments/{tournament}/check-completion'
 */
export const checkCompletion = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: checkCompletion.url(args, options),
    method: 'get',
})

checkCompletion.definition = {
    methods: ["get","head"],
    url: '/api/admin/tournaments/{tournament}/check-completion',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Admin\TournamentController::checkCompletion
 * @see app/Http/Controllers/Admin/TournamentController.php:256
 * @route '/api/admin/tournaments/{tournament}/check-completion'
 */
checkCompletion.url = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { tournament: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    tournament: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        tournament: args.tournament,
                }

    return checkCompletion.definition.url
            .replace('{tournament}', parsedArgs.tournament.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\TournamentController::checkCompletion
 * @see app/Http/Controllers/Admin/TournamentController.php:256
 * @route '/api/admin/tournaments/{tournament}/check-completion'
 */
checkCompletion.get = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: checkCompletion.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Admin\TournamentController::checkCompletion
 * @see app/Http/Controllers/Admin/TournamentController.php:256
 * @route '/api/admin/tournaments/{tournament}/check-completion'
 */
checkCompletion.head = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: checkCompletion.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Admin\TournamentController::checkCompletion
 * @see app/Http/Controllers/Admin/TournamentController.php:256
 * @route '/api/admin/tournaments/{tournament}/check-completion'
 */
    const checkCompletionForm = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: checkCompletion.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Admin\TournamentController::checkCompletion
 * @see app/Http/Controllers/Admin/TournamentController.php:256
 * @route '/api/admin/tournaments/{tournament}/check-completion'
 */
        checkCompletionForm.get = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: checkCompletion.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Admin\TournamentController::checkCompletion
 * @see app/Http/Controllers/Admin/TournamentController.php:256
 * @route '/api/admin/tournaments/{tournament}/check-completion'
 */
        checkCompletionForm.head = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: checkCompletion.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    checkCompletion.form = checkCompletionForm
/**
* @see \App\Http\Controllers\Admin\TournamentController::matches
 * @see app/Http/Controllers/Admin/TournamentController.php:275
 * @route '/api/admin/tournaments/{tournament}/matches'
 */
export const matches = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: matches.url(args, options),
    method: 'get',
})

matches.definition = {
    methods: ["get","head"],
    url: '/api/admin/tournaments/{tournament}/matches',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Admin\TournamentController::matches
 * @see app/Http/Controllers/Admin/TournamentController.php:275
 * @route '/api/admin/tournaments/{tournament}/matches'
 */
matches.url = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { tournament: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    tournament: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        tournament: args.tournament,
                }

    return matches.definition.url
            .replace('{tournament}', parsedArgs.tournament.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\TournamentController::matches
 * @see app/Http/Controllers/Admin/TournamentController.php:275
 * @route '/api/admin/tournaments/{tournament}/matches'
 */
matches.get = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: matches.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Admin\TournamentController::matches
 * @see app/Http/Controllers/Admin/TournamentController.php:275
 * @route '/api/admin/tournaments/{tournament}/matches'
 */
matches.head = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: matches.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Admin\TournamentController::matches
 * @see app/Http/Controllers/Admin/TournamentController.php:275
 * @route '/api/admin/tournaments/{tournament}/matches'
 */
    const matchesForm = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: matches.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Admin\TournamentController::matches
 * @see app/Http/Controllers/Admin/TournamentController.php:275
 * @route '/api/admin/tournaments/{tournament}/matches'
 */
        matchesForm.get = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: matches.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Admin\TournamentController::matches
 * @see app/Http/Controllers/Admin/TournamentController.php:275
 * @route '/api/admin/tournaments/{tournament}/matches'
 */
        matchesForm.head = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: matches.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    matches.form = matchesForm
/**
* @see \App\Http\Controllers\Admin\TournamentController::statistics
 * @see app/Http/Controllers/Admin/TournamentController.php:304
 * @route '/api/admin/tournaments/{tournament}/statistics'
 */
export const statistics = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: statistics.url(args, options),
    method: 'get',
})

statistics.definition = {
    methods: ["get","head"],
    url: '/api/admin/tournaments/{tournament}/statistics',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Admin\TournamentController::statistics
 * @see app/Http/Controllers/Admin/TournamentController.php:304
 * @route '/api/admin/tournaments/{tournament}/statistics'
 */
statistics.url = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { tournament: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    tournament: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        tournament: args.tournament,
                }

    return statistics.definition.url
            .replace('{tournament}', parsedArgs.tournament.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\TournamentController::statistics
 * @see app/Http/Controllers/Admin/TournamentController.php:304
 * @route '/api/admin/tournaments/{tournament}/statistics'
 */
statistics.get = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: statistics.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Admin\TournamentController::statistics
 * @see app/Http/Controllers/Admin/TournamentController.php:304
 * @route '/api/admin/tournaments/{tournament}/statistics'
 */
statistics.head = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: statistics.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Admin\TournamentController::statistics
 * @see app/Http/Controllers/Admin/TournamentController.php:304
 * @route '/api/admin/tournaments/{tournament}/statistics'
 */
    const statisticsForm = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: statistics.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Admin\TournamentController::statistics
 * @see app/Http/Controllers/Admin/TournamentController.php:304
 * @route '/api/admin/tournaments/{tournament}/statistics'
 */
        statisticsForm.get = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: statistics.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Admin\TournamentController::statistics
 * @see app/Http/Controllers/Admin/TournamentController.php:304
 * @route '/api/admin/tournaments/{tournament}/statistics'
 */
        statisticsForm.head = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: statistics.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    statistics.form = statisticsForm
/**
* @see \App\Http\Controllers\Admin\TournamentController::pendingApprovals
 * @see app/Http/Controllers/Admin/TournamentController.php:356
 * @route '/api/admin/tournaments/{tournament}/pending-approvals'
 */
export const pendingApprovals = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: pendingApprovals.url(args, options),
    method: 'get',
})

pendingApprovals.definition = {
    methods: ["get","head"],
    url: '/api/admin/tournaments/{tournament}/pending-approvals',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Admin\TournamentController::pendingApprovals
 * @see app/Http/Controllers/Admin/TournamentController.php:356
 * @route '/api/admin/tournaments/{tournament}/pending-approvals'
 */
pendingApprovals.url = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { tournament: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    tournament: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        tournament: args.tournament,
                }

    return pendingApprovals.definition.url
            .replace('{tournament}', parsedArgs.tournament.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\TournamentController::pendingApprovals
 * @see app/Http/Controllers/Admin/TournamentController.php:356
 * @route '/api/admin/tournaments/{tournament}/pending-approvals'
 */
pendingApprovals.get = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: pendingApprovals.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Admin\TournamentController::pendingApprovals
 * @see app/Http/Controllers/Admin/TournamentController.php:356
 * @route '/api/admin/tournaments/{tournament}/pending-approvals'
 */
pendingApprovals.head = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: pendingApprovals.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Admin\TournamentController::pendingApprovals
 * @see app/Http/Controllers/Admin/TournamentController.php:356
 * @route '/api/admin/tournaments/{tournament}/pending-approvals'
 */
    const pendingApprovalsForm = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: pendingApprovals.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Admin\TournamentController::pendingApprovals
 * @see app/Http/Controllers/Admin/TournamentController.php:356
 * @route '/api/admin/tournaments/{tournament}/pending-approvals'
 */
        pendingApprovalsForm.get = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: pendingApprovals.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Admin\TournamentController::pendingApprovals
 * @see app/Http/Controllers/Admin/TournamentController.php:356
 * @route '/api/admin/tournaments/{tournament}/pending-approvals'
 */
        pendingApprovalsForm.head = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: pendingApprovals.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    pendingApprovals.form = pendingApprovalsForm
/**
* @see \App\Http\Controllers\Admin\TournamentController::show
 * @see app/Http/Controllers/Admin/TournamentController.php:84
 * @route '/api/admin/tournaments/{tournament}'
 */
export const show = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/admin/tournaments/{tournament}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Admin\TournamentController::show
 * @see app/Http/Controllers/Admin/TournamentController.php:84
 * @route '/api/admin/tournaments/{tournament}'
 */
show.url = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return show.definition.url
            .replace('{tournament}', parsedArgs.tournament.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\TournamentController::show
 * @see app/Http/Controllers/Admin/TournamentController.php:84
 * @route '/api/admin/tournaments/{tournament}'
 */
show.get = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Admin\TournamentController::show
 * @see app/Http/Controllers/Admin/TournamentController.php:84
 * @route '/api/admin/tournaments/{tournament}'
 */
show.head = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Admin\TournamentController::show
 * @see app/Http/Controllers/Admin/TournamentController.php:84
 * @route '/api/admin/tournaments/{tournament}'
 */
    const showForm = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: show.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Admin\TournamentController::show
 * @see app/Http/Controllers/Admin/TournamentController.php:84
 * @route '/api/admin/tournaments/{tournament}'
 */
        showForm.get = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: show.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Admin\TournamentController::show
 * @see app/Http/Controllers/Admin/TournamentController.php:84
 * @route '/api/admin/tournaments/{tournament}'
 */
        showForm.head = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: show.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    show.form = showForm
/**
* @see \App\Http\Controllers\Admin\TournamentController::update
 * @see app/Http/Controllers/Admin/TournamentController.php:99
 * @route '/api/admin/tournaments/{tournament}'
 */
export const update = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/api/admin/tournaments/{tournament}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\Admin\TournamentController::update
 * @see app/Http/Controllers/Admin/TournamentController.php:99
 * @route '/api/admin/tournaments/{tournament}'
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
* @see \App\Http\Controllers\Admin\TournamentController::update
 * @see app/Http/Controllers/Admin/TournamentController.php:99
 * @route '/api/admin/tournaments/{tournament}'
 */
update.put = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

    /**
* @see \App\Http\Controllers\Admin\TournamentController::update
 * @see app/Http/Controllers/Admin/TournamentController.php:99
 * @route '/api/admin/tournaments/{tournament}'
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
* @see \App\Http\Controllers\Admin\TournamentController::update
 * @see app/Http/Controllers/Admin/TournamentController.php:99
 * @route '/api/admin/tournaments/{tournament}'
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
* @see \App\Http\Controllers\Admin\TournamentController::destroy
 * @see app/Http/Controllers/Admin/TournamentController.php:133
 * @route '/api/admin/tournaments/{tournament}'
 */
export const destroy = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/api/admin/tournaments/{tournament}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\Admin\TournamentController::destroy
 * @see app/Http/Controllers/Admin/TournamentController.php:133
 * @route '/api/admin/tournaments/{tournament}'
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
* @see \App\Http\Controllers\Admin\TournamentController::destroy
 * @see app/Http/Controllers/Admin/TournamentController.php:133
 * @route '/api/admin/tournaments/{tournament}'
 */
destroy.delete = (args: { tournament: number | { id: number } } | [tournament: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

    /**
* @see \App\Http\Controllers\Admin\TournamentController::destroy
 * @see app/Http/Controllers/Admin/TournamentController.php:133
 * @route '/api/admin/tournaments/{tournament}'
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
* @see \App\Http\Controllers\Admin\TournamentController::destroy
 * @see app/Http/Controllers/Admin/TournamentController.php:133
 * @route '/api/admin/tournaments/{tournament}'
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
* @see \App\Http\Controllers\Admin\TournamentController::updateAutomationMode
 * @see app/Http/Controllers/Admin/TournamentController.php:338
 * @route '/api/admin/tournaments/{tournament}/automation-mode'
 */
export const updateAutomationMode = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateAutomationMode.url(args, options),
    method: 'put',
})

updateAutomationMode.definition = {
    methods: ["put"],
    url: '/api/admin/tournaments/{tournament}/automation-mode',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\Admin\TournamentController::updateAutomationMode
 * @see app/Http/Controllers/Admin/TournamentController.php:338
 * @route '/api/admin/tournaments/{tournament}/automation-mode'
 */
updateAutomationMode.url = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { tournament: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    tournament: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        tournament: args.tournament,
                }

    return updateAutomationMode.definition.url
            .replace('{tournament}', parsedArgs.tournament.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\TournamentController::updateAutomationMode
 * @see app/Http/Controllers/Admin/TournamentController.php:338
 * @route '/api/admin/tournaments/{tournament}/automation-mode'
 */
updateAutomationMode.put = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateAutomationMode.url(args, options),
    method: 'put',
})

    /**
* @see \App\Http\Controllers\Admin\TournamentController::updateAutomationMode
 * @see app/Http/Controllers/Admin/TournamentController.php:338
 * @route '/api/admin/tournaments/{tournament}/automation-mode'
 */
    const updateAutomationModeForm = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: updateAutomationMode.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'PUT',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Admin\TournamentController::updateAutomationMode
 * @see app/Http/Controllers/Admin/TournamentController.php:338
 * @route '/api/admin/tournaments/{tournament}/automation-mode'
 */
        updateAutomationModeForm.put = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: updateAutomationMode.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'PUT',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    updateAutomationMode.form = updateAutomationModeForm
const TournamentController = { initialize, generateNextRound, checkCompletion, matches, statistics, pendingApprovals, show, update, destroy, updateAutomationMode }

export default TournamentController