import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\MatchController::index
 * @see app/Http/Controllers/Api/MatchController.php:32
 * @route '/api/matches'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/matches',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\MatchController::index
 * @see app/Http/Controllers/Api/MatchController.php:32
 * @route '/api/matches'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\MatchController::index
 * @see app/Http/Controllers/Api/MatchController.php:32
 * @route '/api/matches'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Api\MatchController::index
 * @see app/Http/Controllers/Api/MatchController.php:32
 * @route '/api/matches'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Api\MatchController::index
 * @see app/Http/Controllers/Api/MatchController.php:32
 * @route '/api/matches'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Api\MatchController::index
 * @see app/Http/Controllers/Api/MatchController.php:32
 * @route '/api/matches'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Api\MatchController::index
 * @see app/Http/Controllers/Api/MatchController.php:32
 * @route '/api/matches'
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
* @see \App\Http\Controllers\Api\MatchController::show
 * @see app/Http/Controllers/Api/MatchController.php:231
 * @route '/api/matches/{match}'
 */
export const show = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/matches/{match}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\MatchController::show
 * @see app/Http/Controllers/Api/MatchController.php:231
 * @route '/api/matches/{match}'
 */
show.url = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { match: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { match: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    match: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        match: typeof args.match === 'object'
                ? args.match.id
                : args.match,
                }

    return show.definition.url
            .replace('{match}', parsedArgs.match.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\MatchController::show
 * @see app/Http/Controllers/Api/MatchController.php:231
 * @route '/api/matches/{match}'
 */
show.get = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Api\MatchController::show
 * @see app/Http/Controllers/Api/MatchController.php:231
 * @route '/api/matches/{match}'
 */
show.head = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Api\MatchController::show
 * @see app/Http/Controllers/Api/MatchController.php:231
 * @route '/api/matches/{match}'
 */
    const showForm = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: show.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Api\MatchController::show
 * @see app/Http/Controllers/Api/MatchController.php:231
 * @route '/api/matches/{match}'
 */
        showForm.get = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: show.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Api\MatchController::show
 * @see app/Http/Controllers/Api/MatchController.php:231
 * @route '/api/matches/{match}'
 */
        showForm.head = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\Api\MatchController::proposeDates
 * @see app/Http/Controllers/Api/MatchController.php:124
 * @route '/api/matches/{match}/propose-dates'
 */
export const proposeDates = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: proposeDates.url(args, options),
    method: 'post',
})

proposeDates.definition = {
    methods: ["post"],
    url: '/api/matches/{match}/propose-dates',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\MatchController::proposeDates
 * @see app/Http/Controllers/Api/MatchController.php:124
 * @route '/api/matches/{match}/propose-dates'
 */
proposeDates.url = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { match: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { match: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    match: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        match: typeof args.match === 'object'
                ? args.match.id
                : args.match,
                }

    return proposeDates.definition.url
            .replace('{match}', parsedArgs.match.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\MatchController::proposeDates
 * @see app/Http/Controllers/Api/MatchController.php:124
 * @route '/api/matches/{match}/propose-dates'
 */
proposeDates.post = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: proposeDates.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Api\MatchController::proposeDates
 * @see app/Http/Controllers/Api/MatchController.php:124
 * @route '/api/matches/{match}/propose-dates'
 */
    const proposeDatesForm = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: proposeDates.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Api\MatchController::proposeDates
 * @see app/Http/Controllers/Api/MatchController.php:124
 * @route '/api/matches/{match}/propose-dates'
 */
        proposeDatesForm.post = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: proposeDates.url(args, options),
            method: 'post',
        })
    
    proposeDates.form = proposeDatesForm
/**
* @see \App\Http\Controllers\Api\MatchController::scheduleMatch
 * @see app/Http/Controllers/Api/MatchController.php:177
 * @route '/api/matches/{match}/schedule'
 */
export const scheduleMatch = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: scheduleMatch.url(args, options),
    method: 'post',
})

scheduleMatch.definition = {
    methods: ["post"],
    url: '/api/matches/{match}/schedule',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\MatchController::scheduleMatch
 * @see app/Http/Controllers/Api/MatchController.php:177
 * @route '/api/matches/{match}/schedule'
 */
scheduleMatch.url = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { match: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { match: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    match: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        match: typeof args.match === 'object'
                ? args.match.id
                : args.match,
                }

    return scheduleMatch.definition.url
            .replace('{match}', parsedArgs.match.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\MatchController::scheduleMatch
 * @see app/Http/Controllers/Api/MatchController.php:177
 * @route '/api/matches/{match}/schedule'
 */
scheduleMatch.post = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: scheduleMatch.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Api\MatchController::scheduleMatch
 * @see app/Http/Controllers/Api/MatchController.php:177
 * @route '/api/matches/{match}/schedule'
 */
    const scheduleMatchForm = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: scheduleMatch.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Api\MatchController::scheduleMatch
 * @see app/Http/Controllers/Api/MatchController.php:177
 * @route '/api/matches/{match}/schedule'
 */
        scheduleMatchForm.post = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: scheduleMatch.url(args, options),
            method: 'post',
        })
    
    scheduleMatch.form = scheduleMatchForm
/**
* @see \App\Http\Controllers\Api\MatchController::selectDates
 * @see app/Http/Controllers/Api/MatchController.php:278
 * @route '/api/matches/{match}/select-dates'
 */
export const selectDates = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: selectDates.url(args, options),
    method: 'post',
})

selectDates.definition = {
    methods: ["post"],
    url: '/api/matches/{match}/select-dates',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\MatchController::selectDates
 * @see app/Http/Controllers/Api/MatchController.php:278
 * @route '/api/matches/{match}/select-dates'
 */
selectDates.url = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { match: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { match: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    match: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        match: typeof args.match === 'object'
                ? args.match.id
                : args.match,
                }

    return selectDates.definition.url
            .replace('{match}', parsedArgs.match.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\MatchController::selectDates
 * @see app/Http/Controllers/Api/MatchController.php:278
 * @route '/api/matches/{match}/select-dates'
 */
selectDates.post = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: selectDates.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Api\MatchController::selectDates
 * @see app/Http/Controllers/Api/MatchController.php:278
 * @route '/api/matches/{match}/select-dates'
 */
    const selectDatesForm = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: selectDates.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Api\MatchController::selectDates
 * @see app/Http/Controllers/Api/MatchController.php:278
 * @route '/api/matches/{match}/select-dates'
 */
        selectDatesForm.post = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: selectDates.url(args, options),
            method: 'post',
        })
    
    selectDates.form = selectDatesForm
/**
* @see \App\Http\Controllers\Api\MatchController::submitWinLoseResult
 * @see app/Http/Controllers/Api/MatchController.php:328
 * @route '/api/matches/{match}/submit-results'
 */
export const submitWinLoseResult = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submitWinLoseResult.url(args, options),
    method: 'post',
})

submitWinLoseResult.definition = {
    methods: ["post"],
    url: '/api/matches/{match}/submit-results',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\MatchController::submitWinLoseResult
 * @see app/Http/Controllers/Api/MatchController.php:328
 * @route '/api/matches/{match}/submit-results'
 */
submitWinLoseResult.url = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { match: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { match: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    match: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        match: typeof args.match === 'object'
                ? args.match.id
                : args.match,
                }

    return submitWinLoseResult.definition.url
            .replace('{match}', parsedArgs.match.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\MatchController::submitWinLoseResult
 * @see app/Http/Controllers/Api/MatchController.php:328
 * @route '/api/matches/{match}/submit-results'
 */
submitWinLoseResult.post = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submitWinLoseResult.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Api\MatchController::submitWinLoseResult
 * @see app/Http/Controllers/Api/MatchController.php:328
 * @route '/api/matches/{match}/submit-results'
 */
    const submitWinLoseResultForm = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: submitWinLoseResult.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Api\MatchController::submitWinLoseResult
 * @see app/Http/Controllers/Api/MatchController.php:328
 * @route '/api/matches/{match}/submit-results'
 */
        submitWinLoseResultForm.post = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: submitWinLoseResult.url(args, options),
            method: 'post',
        })
    
    submitWinLoseResult.form = submitWinLoseResultForm
/**
* @see \App\Http\Controllers\Api\MatchController::submitPointsResult
 * @see app/Http/Controllers/Api/MatchController.php:494
 * @route '/api/matches/{match}/submit-points'
 */
export const submitPointsResult = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submitPointsResult.url(args, options),
    method: 'post',
})

submitPointsResult.definition = {
    methods: ["post"],
    url: '/api/matches/{match}/submit-points',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\MatchController::submitPointsResult
 * @see app/Http/Controllers/Api/MatchController.php:494
 * @route '/api/matches/{match}/submit-points'
 */
submitPointsResult.url = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { match: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { match: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    match: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        match: typeof args.match === 'object'
                ? args.match.id
                : args.match,
                }

    return submitPointsResult.definition.url
            .replace('{match}', parsedArgs.match.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\MatchController::submitPointsResult
 * @see app/Http/Controllers/Api/MatchController.php:494
 * @route '/api/matches/{match}/submit-points'
 */
submitPointsResult.post = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submitPointsResult.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Api\MatchController::submitPointsResult
 * @see app/Http/Controllers/Api/MatchController.php:494
 * @route '/api/matches/{match}/submit-points'
 */
    const submitPointsResultForm = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: submitPointsResult.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Api\MatchController::submitPointsResult
 * @see app/Http/Controllers/Api/MatchController.php:494
 * @route '/api/matches/{match}/submit-points'
 */
        submitPointsResultForm.post = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: submitPointsResult.url(args, options),
            method: 'post',
        })
    
    submitPointsResult.form = submitPointsResultForm
/**
* @see \App\Http\Controllers\Api\MatchController::confirmResults
 * @see app/Http/Controllers/Api/MatchController.php:622
 * @route '/api/matches/{match}/confirm-results'
 */
export const confirmResults = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirmResults.url(args, options),
    method: 'post',
})

confirmResults.definition = {
    methods: ["post"],
    url: '/api/matches/{match}/confirm-results',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\MatchController::confirmResults
 * @see app/Http/Controllers/Api/MatchController.php:622
 * @route '/api/matches/{match}/confirm-results'
 */
confirmResults.url = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { match: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { match: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    match: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        match: typeof args.match === 'object'
                ? args.match.id
                : args.match,
                }

    return confirmResults.definition.url
            .replace('{match}', parsedArgs.match.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\MatchController::confirmResults
 * @see app/Http/Controllers/Api/MatchController.php:622
 * @route '/api/matches/{match}/confirm-results'
 */
confirmResults.post = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirmResults.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Api\MatchController::confirmResults
 * @see app/Http/Controllers/Api/MatchController.php:622
 * @route '/api/matches/{match}/confirm-results'
 */
    const confirmResultsForm = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: confirmResults.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Api\MatchController::confirmResults
 * @see app/Http/Controllers/Api/MatchController.php:622
 * @route '/api/matches/{match}/confirm-results'
 */
        confirmResultsForm.post = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: confirmResults.url(args, options),
            method: 'post',
        })
    
    confirmResults.form = confirmResultsForm
/**
* @see \App\Http\Controllers\Api\MatchController::forfeitMatch
 * @see app/Http/Controllers/Api/MatchController.php:729
 * @route '/api/matches/{match}/forfeit'
 */
export const forfeitMatch = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: forfeitMatch.url(args, options),
    method: 'post',
})

forfeitMatch.definition = {
    methods: ["post"],
    url: '/api/matches/{match}/forfeit',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\MatchController::forfeitMatch
 * @see app/Http/Controllers/Api/MatchController.php:729
 * @route '/api/matches/{match}/forfeit'
 */
forfeitMatch.url = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { match: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { match: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    match: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        match: typeof args.match === 'object'
                ? args.match.id
                : args.match,
                }

    return forfeitMatch.definition.url
            .replace('{match}', parsedArgs.match.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\MatchController::forfeitMatch
 * @see app/Http/Controllers/Api/MatchController.php:729
 * @route '/api/matches/{match}/forfeit'
 */
forfeitMatch.post = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: forfeitMatch.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Api\MatchController::forfeitMatch
 * @see app/Http/Controllers/Api/MatchController.php:729
 * @route '/api/matches/{match}/forfeit'
 */
    const forfeitMatchForm = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: forfeitMatch.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Api\MatchController::forfeitMatch
 * @see app/Http/Controllers/Api/MatchController.php:729
 * @route '/api/matches/{match}/forfeit'
 */
        forfeitMatchForm.post = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: forfeitMatch.url(args, options),
            method: 'post',
        })
    
    forfeitMatch.form = forfeitMatchForm
/**
* @see \App\Http\Controllers\Api\MatchController::getMessages
 * @see app/Http/Controllers/Api/MatchController.php:784
 * @route '/api/matches/{match}/messages'
 */
export const getMessages = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getMessages.url(args, options),
    method: 'get',
})

getMessages.definition = {
    methods: ["get","head"],
    url: '/api/matches/{match}/messages',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\MatchController::getMessages
 * @see app/Http/Controllers/Api/MatchController.php:784
 * @route '/api/matches/{match}/messages'
 */
getMessages.url = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { match: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { match: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    match: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        match: typeof args.match === 'object'
                ? args.match.id
                : args.match,
                }

    return getMessages.definition.url
            .replace('{match}', parsedArgs.match.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\MatchController::getMessages
 * @see app/Http/Controllers/Api/MatchController.php:784
 * @route '/api/matches/{match}/messages'
 */
getMessages.get = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getMessages.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Api\MatchController::getMessages
 * @see app/Http/Controllers/Api/MatchController.php:784
 * @route '/api/matches/{match}/messages'
 */
getMessages.head = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: getMessages.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Api\MatchController::getMessages
 * @see app/Http/Controllers/Api/MatchController.php:784
 * @route '/api/matches/{match}/messages'
 */
    const getMessagesForm = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: getMessages.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Api\MatchController::getMessages
 * @see app/Http/Controllers/Api/MatchController.php:784
 * @route '/api/matches/{match}/messages'
 */
        getMessagesForm.get = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getMessages.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Api\MatchController::getMessages
 * @see app/Http/Controllers/Api/MatchController.php:784
 * @route '/api/matches/{match}/messages'
 */
        getMessagesForm.head = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getMessages.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    getMessages.form = getMessagesForm
/**
* @see \App\Http\Controllers\Api\MatchController::sendMessage
 * @see app/Http/Controllers/Api/MatchController.php:817
 * @route '/api/matches/{match}/messages'
 */
export const sendMessage = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: sendMessage.url(args, options),
    method: 'post',
})

sendMessage.definition = {
    methods: ["post"],
    url: '/api/matches/{match}/messages',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\MatchController::sendMessage
 * @see app/Http/Controllers/Api/MatchController.php:817
 * @route '/api/matches/{match}/messages'
 */
sendMessage.url = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { match: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { match: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    match: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        match: typeof args.match === 'object'
                ? args.match.id
                : args.match,
                }

    return sendMessage.definition.url
            .replace('{match}', parsedArgs.match.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\MatchController::sendMessage
 * @see app/Http/Controllers/Api/MatchController.php:817
 * @route '/api/matches/{match}/messages'
 */
sendMessage.post = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: sendMessage.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Api\MatchController::sendMessage
 * @see app/Http/Controllers/Api/MatchController.php:817
 * @route '/api/matches/{match}/messages'
 */
    const sendMessageForm = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: sendMessage.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Api\MatchController::sendMessage
 * @see app/Http/Controllers/Api/MatchController.php:817
 * @route '/api/matches/{match}/messages'
 */
        sendMessageForm.post = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: sendMessage.url(args, options),
            method: 'post',
        })
    
    sendMessage.form = sendMessageForm
/**
* @see \App\Http\Controllers\Api\MatchController::getStatus
 * @see app/Http/Controllers/Api/MatchController.php:1198
 * @route '/api/matches/{match}/status'
 */
export const getStatus = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getStatus.url(args, options),
    method: 'get',
})

getStatus.definition = {
    methods: ["get","head"],
    url: '/api/matches/{match}/status',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\MatchController::getStatus
 * @see app/Http/Controllers/Api/MatchController.php:1198
 * @route '/api/matches/{match}/status'
 */
getStatus.url = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { match: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { match: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    match: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        match: typeof args.match === 'object'
                ? args.match.id
                : args.match,
                }

    return getStatus.definition.url
            .replace('{match}', parsedArgs.match.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\MatchController::getStatus
 * @see app/Http/Controllers/Api/MatchController.php:1198
 * @route '/api/matches/{match}/status'
 */
getStatus.get = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getStatus.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Api\MatchController::getStatus
 * @see app/Http/Controllers/Api/MatchController.php:1198
 * @route '/api/matches/{match}/status'
 */
getStatus.head = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: getStatus.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Api\MatchController::getStatus
 * @see app/Http/Controllers/Api/MatchController.php:1198
 * @route '/api/matches/{match}/status'
 */
    const getStatusForm = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: getStatus.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Api\MatchController::getStatus
 * @see app/Http/Controllers/Api/MatchController.php:1198
 * @route '/api/matches/{match}/status'
 */
        getStatusForm.get = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getStatus.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Api\MatchController::getStatus
 * @see app/Http/Controllers/Api/MatchController.php:1198
 * @route '/api/matches/{match}/status'
 */
        getStatusForm.head = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getStatus.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    getStatus.form = getStatusForm
/**
* @see \App\Http\Controllers\Api\MatchController::getMessagesSince
 * @see app/Http/Controllers/Api/MatchController.php:1230
 * @route '/api/matches/{match}/messages/since/{timestamp}'
 */
export const getMessagesSince = (args: { match: number | { id: number }, timestamp: string | number } | [match: number | { id: number }, timestamp: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getMessagesSince.url(args, options),
    method: 'get',
})

getMessagesSince.definition = {
    methods: ["get","head"],
    url: '/api/matches/{match}/messages/since/{timestamp}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\MatchController::getMessagesSince
 * @see app/Http/Controllers/Api/MatchController.php:1230
 * @route '/api/matches/{match}/messages/since/{timestamp}'
 */
getMessagesSince.url = (args: { match: number | { id: number }, timestamp: string | number } | [match: number | { id: number }, timestamp: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
                    match: args[0],
                    timestamp: args[1],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        match: typeof args.match === 'object'
                ? args.match.id
                : args.match,
                                timestamp: args.timestamp,
                }

    return getMessagesSince.definition.url
            .replace('{match}', parsedArgs.match.toString())
            .replace('{timestamp}', parsedArgs.timestamp.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\MatchController::getMessagesSince
 * @see app/Http/Controllers/Api/MatchController.php:1230
 * @route '/api/matches/{match}/messages/since/{timestamp}'
 */
getMessagesSince.get = (args: { match: number | { id: number }, timestamp: string | number } | [match: number | { id: number }, timestamp: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getMessagesSince.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Api\MatchController::getMessagesSince
 * @see app/Http/Controllers/Api/MatchController.php:1230
 * @route '/api/matches/{match}/messages/since/{timestamp}'
 */
getMessagesSince.head = (args: { match: number | { id: number }, timestamp: string | number } | [match: number | { id: number }, timestamp: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: getMessagesSince.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Api\MatchController::getMessagesSince
 * @see app/Http/Controllers/Api/MatchController.php:1230
 * @route '/api/matches/{match}/messages/since/{timestamp}'
 */
    const getMessagesSinceForm = (args: { match: number | { id: number }, timestamp: string | number } | [match: number | { id: number }, timestamp: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: getMessagesSince.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Api\MatchController::getMessagesSince
 * @see app/Http/Controllers/Api/MatchController.php:1230
 * @route '/api/matches/{match}/messages/since/{timestamp}'
 */
        getMessagesSinceForm.get = (args: { match: number | { id: number }, timestamp: string | number } | [match: number | { id: number }, timestamp: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getMessagesSince.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Api\MatchController::getMessagesSince
 * @see app/Http/Controllers/Api/MatchController.php:1230
 * @route '/api/matches/{match}/messages/since/{timestamp}'
 */
        getMessagesSinceForm.head = (args: { match: number | { id: number }, timestamp: string | number } | [match: number | { id: number }, timestamp: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getMessagesSince.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    getMessagesSince.form = getMessagesSinceForm
const MatchController = { index, show, proposeDates, scheduleMatch, selectDates, submitWinLoseResult, submitPointsResult, confirmResults, forfeitMatch, getMessages, sendMessage, getStatus, getMessagesSince }

export default MatchController