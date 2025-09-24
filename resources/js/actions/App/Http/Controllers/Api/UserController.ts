import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\UserController::dashboard
 * @see app/Http/Controllers/Api/UserController.php:49
 * @route '/api/dashboard'
 */
const dashboard79e8db78b7285f47b9383df06923ad39 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard79e8db78b7285f47b9383df06923ad39.url(options),
    method: 'get',
})

dashboard79e8db78b7285f47b9383df06923ad39.definition = {
    methods: ["get","head"],
    url: '/api/dashboard',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\UserController::dashboard
 * @see app/Http/Controllers/Api/UserController.php:49
 * @route '/api/dashboard'
 */
dashboard79e8db78b7285f47b9383df06923ad39.url = (options?: RouteQueryOptions) => {
    return dashboard79e8db78b7285f47b9383df06923ad39.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\UserController::dashboard
 * @see app/Http/Controllers/Api/UserController.php:49
 * @route '/api/dashboard'
 */
dashboard79e8db78b7285f47b9383df06923ad39.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard79e8db78b7285f47b9383df06923ad39.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Api\UserController::dashboard
 * @see app/Http/Controllers/Api/UserController.php:49
 * @route '/api/dashboard'
 */
dashboard79e8db78b7285f47b9383df06923ad39.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: dashboard79e8db78b7285f47b9383df06923ad39.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Api\UserController::dashboard
 * @see app/Http/Controllers/Api/UserController.php:49
 * @route '/api/dashboard'
 */
    const dashboard79e8db78b7285f47b9383df06923ad39Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: dashboard79e8db78b7285f47b9383df06923ad39.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Api\UserController::dashboard
 * @see app/Http/Controllers/Api/UserController.php:49
 * @route '/api/dashboard'
 */
        dashboard79e8db78b7285f47b9383df06923ad39Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: dashboard79e8db78b7285f47b9383df06923ad39.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Api\UserController::dashboard
 * @see app/Http/Controllers/Api/UserController.php:49
 * @route '/api/dashboard'
 */
        dashboard79e8db78b7285f47b9383df06923ad39Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: dashboard79e8db78b7285f47b9383df06923ad39.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    dashboard79e8db78b7285f47b9383df06923ad39.form = dashboard79e8db78b7285f47b9383df06923ad39Form
    /**
* @see \App\Http\Controllers\Api\UserController::dashboard
 * @see app/Http/Controllers/Api/UserController.php:49
 * @route '/api/user/dashboard'
 */
const dashboard5ff0e4591959cda4a7baf64e5190bdd5 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard5ff0e4591959cda4a7baf64e5190bdd5.url(options),
    method: 'get',
})

dashboard5ff0e4591959cda4a7baf64e5190bdd5.definition = {
    methods: ["get","head"],
    url: '/api/user/dashboard',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\UserController::dashboard
 * @see app/Http/Controllers/Api/UserController.php:49
 * @route '/api/user/dashboard'
 */
dashboard5ff0e4591959cda4a7baf64e5190bdd5.url = (options?: RouteQueryOptions) => {
    return dashboard5ff0e4591959cda4a7baf64e5190bdd5.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\UserController::dashboard
 * @see app/Http/Controllers/Api/UserController.php:49
 * @route '/api/user/dashboard'
 */
dashboard5ff0e4591959cda4a7baf64e5190bdd5.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard5ff0e4591959cda4a7baf64e5190bdd5.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Api\UserController::dashboard
 * @see app/Http/Controllers/Api/UserController.php:49
 * @route '/api/user/dashboard'
 */
dashboard5ff0e4591959cda4a7baf64e5190bdd5.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: dashboard5ff0e4591959cda4a7baf64e5190bdd5.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Api\UserController::dashboard
 * @see app/Http/Controllers/Api/UserController.php:49
 * @route '/api/user/dashboard'
 */
    const dashboard5ff0e4591959cda4a7baf64e5190bdd5Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: dashboard5ff0e4591959cda4a7baf64e5190bdd5.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Api\UserController::dashboard
 * @see app/Http/Controllers/Api/UserController.php:49
 * @route '/api/user/dashboard'
 */
        dashboard5ff0e4591959cda4a7baf64e5190bdd5Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: dashboard5ff0e4591959cda4a7baf64e5190bdd5.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Api\UserController::dashboard
 * @see app/Http/Controllers/Api/UserController.php:49
 * @route '/api/user/dashboard'
 */
        dashboard5ff0e4591959cda4a7baf64e5190bdd5Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: dashboard5ff0e4591959cda4a7baf64e5190bdd5.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    dashboard5ff0e4591959cda4a7baf64e5190bdd5.form = dashboard5ff0e4591959cda4a7baf64e5190bdd5Form

export const dashboard = {
    '/api/dashboard': dashboard79e8db78b7285f47b9383df06923ad39,
    '/api/user/dashboard': dashboard5ff0e4591959cda4a7baf64e5190bdd5,
}

/**
* @see \App\Http\Controllers\Api\UserController::statistics
 * @see app/Http/Controllers/Api/UserController.php:183
 * @route '/api/statistics'
 */
export const statistics = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: statistics.url(options),
    method: 'get',
})

statistics.definition = {
    methods: ["get","head"],
    url: '/api/statistics',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\UserController::statistics
 * @see app/Http/Controllers/Api/UserController.php:183
 * @route '/api/statistics'
 */
statistics.url = (options?: RouteQueryOptions) => {
    return statistics.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\UserController::statistics
 * @see app/Http/Controllers/Api/UserController.php:183
 * @route '/api/statistics'
 */
statistics.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: statistics.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Api\UserController::statistics
 * @see app/Http/Controllers/Api/UserController.php:183
 * @route '/api/statistics'
 */
statistics.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: statistics.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Api\UserController::statistics
 * @see app/Http/Controllers/Api/UserController.php:183
 * @route '/api/statistics'
 */
    const statisticsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: statistics.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Api\UserController::statistics
 * @see app/Http/Controllers/Api/UserController.php:183
 * @route '/api/statistics'
 */
        statisticsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: statistics.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Api\UserController::statistics
 * @see app/Http/Controllers/Api/UserController.php:183
 * @route '/api/statistics'
 */
        statisticsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: statistics.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    statistics.form = statisticsForm
/**
* @see \App\Http\Controllers\Api\UserController::updateCommunity
 * @see app/Http/Controllers/Api/UserController.php:229
 * @route '/api/user/update-community'
 */
export const updateCommunity = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateCommunity.url(options),
    method: 'put',
})

updateCommunity.definition = {
    methods: ["put"],
    url: '/api/user/update-community',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\Api\UserController::updateCommunity
 * @see app/Http/Controllers/Api/UserController.php:229
 * @route '/api/user/update-community'
 */
updateCommunity.url = (options?: RouteQueryOptions) => {
    return updateCommunity.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\UserController::updateCommunity
 * @see app/Http/Controllers/Api/UserController.php:229
 * @route '/api/user/update-community'
 */
updateCommunity.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateCommunity.url(options),
    method: 'put',
})

    /**
* @see \App\Http\Controllers\Api\UserController::updateCommunity
 * @see app/Http/Controllers/Api/UserController.php:229
 * @route '/api/user/update-community'
 */
    const updateCommunityForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: updateCommunity.url({
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'PUT',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Api\UserController::updateCommunity
 * @see app/Http/Controllers/Api/UserController.php:229
 * @route '/api/user/update-community'
 */
        updateCommunityForm.put = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: updateCommunity.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'PUT',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    updateCommunity.form = updateCommunityForm
/**
* @see \App\Http\Controllers\Api\UserController::updateProfileImage
 * @see app/Http/Controllers/Api/UserController.php:298
 * @route '/api/user/update-profile-image'
 */
export const updateProfileImage = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: updateProfileImage.url(options),
    method: 'post',
})

updateProfileImage.definition = {
    methods: ["post"],
    url: '/api/user/update-profile-image',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\UserController::updateProfileImage
 * @see app/Http/Controllers/Api/UserController.php:298
 * @route '/api/user/update-profile-image'
 */
updateProfileImage.url = (options?: RouteQueryOptions) => {
    return updateProfileImage.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\UserController::updateProfileImage
 * @see app/Http/Controllers/Api/UserController.php:298
 * @route '/api/user/update-profile-image'
 */
updateProfileImage.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: updateProfileImage.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Api\UserController::updateProfileImage
 * @see app/Http/Controllers/Api/UserController.php:298
 * @route '/api/user/update-profile-image'
 */
    const updateProfileImageForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: updateProfileImage.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Api\UserController::updateProfileImage
 * @see app/Http/Controllers/Api/UserController.php:298
 * @route '/api/user/update-profile-image'
 */
        updateProfileImageForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: updateProfileImage.url(options),
            method: 'post',
        })
    
    updateProfileImage.form = updateProfileImageForm
/**
* @see \App\Http\Controllers\Api\UserController::index
 * @see app/Http/Controllers/Api/UserController.php:20
 * @route '/api/users'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/users',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\UserController::index
 * @see app/Http/Controllers/Api/UserController.php:20
 * @route '/api/users'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\UserController::index
 * @see app/Http/Controllers/Api/UserController.php:20
 * @route '/api/users'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Api\UserController::index
 * @see app/Http/Controllers/Api/UserController.php:20
 * @route '/api/users'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Api\UserController::index
 * @see app/Http/Controllers/Api/UserController.php:20
 * @route '/api/users'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Api\UserController::index
 * @see app/Http/Controllers/Api/UserController.php:20
 * @route '/api/users'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Api\UserController::index
 * @see app/Http/Controllers/Api/UserController.php:20
 * @route '/api/users'
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
const UserController = { dashboard, statistics, updateCommunity, updateProfileImage, index }

export default UserController