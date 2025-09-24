import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\PlayerController::updateProfile
 * @see app/Http/Controllers/PlayerController.php:16
 * @route '/api/user/profile'
 */
export const updateProfile = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateProfile.url(options),
    method: 'put',
})

updateProfile.definition = {
    methods: ["put"],
    url: '/api/user/profile',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\PlayerController::updateProfile
 * @see app/Http/Controllers/PlayerController.php:16
 * @route '/api/user/profile'
 */
updateProfile.url = (options?: RouteQueryOptions) => {
    return updateProfile.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PlayerController::updateProfile
 * @see app/Http/Controllers/PlayerController.php:16
 * @route '/api/user/profile'
 */
updateProfile.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateProfile.url(options),
    method: 'put',
})

    /**
* @see \App\Http\Controllers\PlayerController::updateProfile
 * @see app/Http/Controllers/PlayerController.php:16
 * @route '/api/user/profile'
 */
    const updateProfileForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: updateProfile.url({
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'PUT',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\PlayerController::updateProfile
 * @see app/Http/Controllers/PlayerController.php:16
 * @route '/api/user/profile'
 */
        updateProfileForm.put = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: updateProfile.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'PUT',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    updateProfile.form = updateProfileForm
/**
* @see \App\Http\Controllers\PlayerController::leaderboard
 * @see app/Http/Controllers/PlayerController.php:46
 * @route '/api/players/leaderboard'
 */
export const leaderboard = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: leaderboard.url(options),
    method: 'get',
})

leaderboard.definition = {
    methods: ["get","head"],
    url: '/api/players/leaderboard',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PlayerController::leaderboard
 * @see app/Http/Controllers/PlayerController.php:46
 * @route '/api/players/leaderboard'
 */
leaderboard.url = (options?: RouteQueryOptions) => {
    return leaderboard.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PlayerController::leaderboard
 * @see app/Http/Controllers/PlayerController.php:46
 * @route '/api/players/leaderboard'
 */
leaderboard.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: leaderboard.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\PlayerController::leaderboard
 * @see app/Http/Controllers/PlayerController.php:46
 * @route '/api/players/leaderboard'
 */
leaderboard.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: leaderboard.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\PlayerController::leaderboard
 * @see app/Http/Controllers/PlayerController.php:46
 * @route '/api/players/leaderboard'
 */
    const leaderboardForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: leaderboard.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\PlayerController::leaderboard
 * @see app/Http/Controllers/PlayerController.php:46
 * @route '/api/players/leaderboard'
 */
        leaderboardForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: leaderboard.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\PlayerController::leaderboard
 * @see app/Http/Controllers/PlayerController.php:46
 * @route '/api/players/leaderboard'
 */
        leaderboardForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: leaderboard.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    leaderboard.form = leaderboardForm
/**
* @see \App\Http\Controllers\PlayerController::personalLeaderboard
 * @see app/Http/Controllers/PlayerController.php:120
 * @route '/api/players/personal-leaderboard'
 */
export const personalLeaderboard = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: personalLeaderboard.url(options),
    method: 'get',
})

personalLeaderboard.definition = {
    methods: ["get","head"],
    url: '/api/players/personal-leaderboard',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PlayerController::personalLeaderboard
 * @see app/Http/Controllers/PlayerController.php:120
 * @route '/api/players/personal-leaderboard'
 */
personalLeaderboard.url = (options?: RouteQueryOptions) => {
    return personalLeaderboard.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PlayerController::personalLeaderboard
 * @see app/Http/Controllers/PlayerController.php:120
 * @route '/api/players/personal-leaderboard'
 */
personalLeaderboard.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: personalLeaderboard.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\PlayerController::personalLeaderboard
 * @see app/Http/Controllers/PlayerController.php:120
 * @route '/api/players/personal-leaderboard'
 */
personalLeaderboard.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: personalLeaderboard.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\PlayerController::personalLeaderboard
 * @see app/Http/Controllers/PlayerController.php:120
 * @route '/api/players/personal-leaderboard'
 */
    const personalLeaderboardForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: personalLeaderboard.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\PlayerController::personalLeaderboard
 * @see app/Http/Controllers/PlayerController.php:120
 * @route '/api/players/personal-leaderboard'
 */
        personalLeaderboardForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: personalLeaderboard.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\PlayerController::personalLeaderboard
 * @see app/Http/Controllers/PlayerController.php:120
 * @route '/api/players/personal-leaderboard'
 */
        personalLeaderboardForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: personalLeaderboard.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    personalLeaderboard.form = personalLeaderboardForm
/**
* @see \App\Http\Controllers\PlayerController::awards
 * @see app/Http/Controllers/PlayerController.php:207
 * @route '/api/players/awards'
 */
export const awards = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: awards.url(options),
    method: 'get',
})

awards.definition = {
    methods: ["get","head"],
    url: '/api/players/awards',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PlayerController::awards
 * @see app/Http/Controllers/PlayerController.php:207
 * @route '/api/players/awards'
 */
awards.url = (options?: RouteQueryOptions) => {
    return awards.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PlayerController::awards
 * @see app/Http/Controllers/PlayerController.php:207
 * @route '/api/players/awards'
 */
awards.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: awards.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\PlayerController::awards
 * @see app/Http/Controllers/PlayerController.php:207
 * @route '/api/players/awards'
 */
awards.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: awards.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\PlayerController::awards
 * @see app/Http/Controllers/PlayerController.php:207
 * @route '/api/players/awards'
 */
    const awardsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: awards.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\PlayerController::awards
 * @see app/Http/Controllers/PlayerController.php:207
 * @route '/api/players/awards'
 */
        awardsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: awards.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\PlayerController::awards
 * @see app/Http/Controllers/PlayerController.php:207
 * @route '/api/players/awards'
 */
        awardsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: awards.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    awards.form = awardsForm
/**
* @see \App\Http\Controllers\PlayerController::playerStats
 * @see app/Http/Controllers/PlayerController.php:348
 * @route '/api/players/{player}/stats'
 */
export const playerStats = (args: { player: string | number } | [player: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: playerStats.url(args, options),
    method: 'get',
})

playerStats.definition = {
    methods: ["get","head"],
    url: '/api/players/{player}/stats',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PlayerController::playerStats
 * @see app/Http/Controllers/PlayerController.php:348
 * @route '/api/players/{player}/stats'
 */
playerStats.url = (args: { player: string | number } | [player: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return playerStats.definition.url
            .replace('{player}', parsedArgs.player.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PlayerController::playerStats
 * @see app/Http/Controllers/PlayerController.php:348
 * @route '/api/players/{player}/stats'
 */
playerStats.get = (args: { player: string | number } | [player: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: playerStats.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\PlayerController::playerStats
 * @see app/Http/Controllers/PlayerController.php:348
 * @route '/api/players/{player}/stats'
 */
playerStats.head = (args: { player: string | number } | [player: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: playerStats.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\PlayerController::playerStats
 * @see app/Http/Controllers/PlayerController.php:348
 * @route '/api/players/{player}/stats'
 */
    const playerStatsForm = (args: { player: string | number } | [player: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: playerStats.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\PlayerController::playerStats
 * @see app/Http/Controllers/PlayerController.php:348
 * @route '/api/players/{player}/stats'
 */
        playerStatsForm.get = (args: { player: string | number } | [player: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: playerStats.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\PlayerController::playerStats
 * @see app/Http/Controllers/PlayerController.php:348
 * @route '/api/players/{player}/stats'
 */
        playerStatsForm.head = (args: { player: string | number } | [player: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: playerStats.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    playerStats.form = playerStatsForm
/**
* @see \App\Http\Controllers\PlayerController::myStats
 * @see app/Http/Controllers/PlayerController.php:443
 * @route '/api/players/my-stats'
 */
export const myStats = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: myStats.url(options),
    method: 'get',
})

myStats.definition = {
    methods: ["get","head"],
    url: '/api/players/my-stats',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PlayerController::myStats
 * @see app/Http/Controllers/PlayerController.php:443
 * @route '/api/players/my-stats'
 */
myStats.url = (options?: RouteQueryOptions) => {
    return myStats.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PlayerController::myStats
 * @see app/Http/Controllers/PlayerController.php:443
 * @route '/api/players/my-stats'
 */
myStats.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: myStats.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\PlayerController::myStats
 * @see app/Http/Controllers/PlayerController.php:443
 * @route '/api/players/my-stats'
 */
myStats.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: myStats.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\PlayerController::myStats
 * @see app/Http/Controllers/PlayerController.php:443
 * @route '/api/players/my-stats'
 */
    const myStatsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: myStats.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\PlayerController::myStats
 * @see app/Http/Controllers/PlayerController.php:443
 * @route '/api/players/my-stats'
 */
        myStatsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: myStats.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\PlayerController::myStats
 * @see app/Http/Controllers/PlayerController.php:443
 * @route '/api/players/my-stats'
 */
        myStatsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: myStats.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    myStats.form = myStatsForm
const PlayerController = { updateProfile, leaderboard, personalLeaderboard, awards, playerStats, myStats }

export default PlayerController