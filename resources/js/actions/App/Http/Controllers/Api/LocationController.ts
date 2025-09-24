import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\LocationController::getRegions
 * @see app/Http/Controllers/Api/LocationController.php:15
 * @route '/api/regions'
 */
export const getRegions = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getRegions.url(options),
    method: 'get',
})

getRegions.definition = {
    methods: ["get","head"],
    url: '/api/regions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\LocationController::getRegions
 * @see app/Http/Controllers/Api/LocationController.php:15
 * @route '/api/regions'
 */
getRegions.url = (options?: RouteQueryOptions) => {
    return getRegions.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\LocationController::getRegions
 * @see app/Http/Controllers/Api/LocationController.php:15
 * @route '/api/regions'
 */
getRegions.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getRegions.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Api\LocationController::getRegions
 * @see app/Http/Controllers/Api/LocationController.php:15
 * @route '/api/regions'
 */
getRegions.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: getRegions.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Api\LocationController::getRegions
 * @see app/Http/Controllers/Api/LocationController.php:15
 * @route '/api/regions'
 */
    const getRegionsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: getRegions.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Api\LocationController::getRegions
 * @see app/Http/Controllers/Api/LocationController.php:15
 * @route '/api/regions'
 */
        getRegionsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getRegions.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Api\LocationController::getRegions
 * @see app/Http/Controllers/Api/LocationController.php:15
 * @route '/api/regions'
 */
        getRegionsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getRegions.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    getRegions.form = getRegionsForm
/**
* @see \App\Http\Controllers\Api\LocationController::getCountiesByRegion
 * @see app/Http/Controllers/Api/LocationController.php:46
 * @route '/api/counties'
 */
export const getCountiesByRegion = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getCountiesByRegion.url(options),
    method: 'get',
})

getCountiesByRegion.definition = {
    methods: ["get","head"],
    url: '/api/counties',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\LocationController::getCountiesByRegion
 * @see app/Http/Controllers/Api/LocationController.php:46
 * @route '/api/counties'
 */
getCountiesByRegion.url = (options?: RouteQueryOptions) => {
    return getCountiesByRegion.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\LocationController::getCountiesByRegion
 * @see app/Http/Controllers/Api/LocationController.php:46
 * @route '/api/counties'
 */
getCountiesByRegion.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getCountiesByRegion.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Api\LocationController::getCountiesByRegion
 * @see app/Http/Controllers/Api/LocationController.php:46
 * @route '/api/counties'
 */
getCountiesByRegion.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: getCountiesByRegion.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Api\LocationController::getCountiesByRegion
 * @see app/Http/Controllers/Api/LocationController.php:46
 * @route '/api/counties'
 */
    const getCountiesByRegionForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: getCountiesByRegion.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Api\LocationController::getCountiesByRegion
 * @see app/Http/Controllers/Api/LocationController.php:46
 * @route '/api/counties'
 */
        getCountiesByRegionForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getCountiesByRegion.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Api\LocationController::getCountiesByRegion
 * @see app/Http/Controllers/Api/LocationController.php:46
 * @route '/api/counties'
 */
        getCountiesByRegionForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getCountiesByRegion.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    getCountiesByRegion.form = getCountiesByRegionForm
/**
* @see \App\Http\Controllers\Api\LocationController::getCommunitiesByCounty
 * @see app/Http/Controllers/Api/LocationController.php:88
 * @route '/api/communities'
 */
export const getCommunitiesByCounty = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getCommunitiesByCounty.url(options),
    method: 'get',
})

getCommunitiesByCounty.definition = {
    methods: ["get","head"],
    url: '/api/communities',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\LocationController::getCommunitiesByCounty
 * @see app/Http/Controllers/Api/LocationController.php:88
 * @route '/api/communities'
 */
getCommunitiesByCounty.url = (options?: RouteQueryOptions) => {
    return getCommunitiesByCounty.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\LocationController::getCommunitiesByCounty
 * @see app/Http/Controllers/Api/LocationController.php:88
 * @route '/api/communities'
 */
getCommunitiesByCounty.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getCommunitiesByCounty.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Api\LocationController::getCommunitiesByCounty
 * @see app/Http/Controllers/Api/LocationController.php:88
 * @route '/api/communities'
 */
getCommunitiesByCounty.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: getCommunitiesByCounty.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Api\LocationController::getCommunitiesByCounty
 * @see app/Http/Controllers/Api/LocationController.php:88
 * @route '/api/communities'
 */
    const getCommunitiesByCountyForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: getCommunitiesByCounty.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Api\LocationController::getCommunitiesByCounty
 * @see app/Http/Controllers/Api/LocationController.php:88
 * @route '/api/communities'
 */
        getCommunitiesByCountyForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getCommunitiesByCounty.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Api\LocationController::getCommunitiesByCounty
 * @see app/Http/Controllers/Api/LocationController.php:88
 * @route '/api/communities'
 */
        getCommunitiesByCountyForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getCommunitiesByCounty.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    getCommunitiesByCounty.form = getCommunitiesByCountyForm
/**
* @see \App\Http\Controllers\Api\LocationController::getAllCounties
 * @see app/Http/Controllers/Api/LocationController.php:130
 * @route '/api/counties/all'
 */
export const getAllCounties = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getAllCounties.url(options),
    method: 'get',
})

getAllCounties.definition = {
    methods: ["get","head"],
    url: '/api/counties/all',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\LocationController::getAllCounties
 * @see app/Http/Controllers/Api/LocationController.php:130
 * @route '/api/counties/all'
 */
getAllCounties.url = (options?: RouteQueryOptions) => {
    return getAllCounties.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\LocationController::getAllCounties
 * @see app/Http/Controllers/Api/LocationController.php:130
 * @route '/api/counties/all'
 */
getAllCounties.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getAllCounties.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Api\LocationController::getAllCounties
 * @see app/Http/Controllers/Api/LocationController.php:130
 * @route '/api/counties/all'
 */
getAllCounties.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: getAllCounties.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Api\LocationController::getAllCounties
 * @see app/Http/Controllers/Api/LocationController.php:130
 * @route '/api/counties/all'
 */
    const getAllCountiesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: getAllCounties.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Api\LocationController::getAllCounties
 * @see app/Http/Controllers/Api/LocationController.php:130
 * @route '/api/counties/all'
 */
        getAllCountiesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getAllCounties.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Api\LocationController::getAllCounties
 * @see app/Http/Controllers/Api/LocationController.php:130
 * @route '/api/counties/all'
 */
        getAllCountiesForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getAllCounties.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    getAllCounties.form = getAllCountiesForm
const LocationController = { getRegions, getCountiesByRegion, getCommunitiesByCounty, getAllCounties }

export default LocationController