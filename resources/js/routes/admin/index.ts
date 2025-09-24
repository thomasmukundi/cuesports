import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
import tournaments from './tournaments'
import communities from './communities'
import matches from './matches'
import players from './players'
import winners from './winners'
import transactions from './transactions'
import api from './api'
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
const admin = {
    dashboard,
tournaments,
communities,
matches,
players,
messages,
winners,
transactions,
logout,
api,
}

export default admin