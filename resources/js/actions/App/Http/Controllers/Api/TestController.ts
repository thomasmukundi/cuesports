import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\TestController::testPushNotification
 * @see app/Http/Controllers/Api/TestController.php:16
 * @route '/api/test/push-notification'
 */
export const testPushNotification = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: testPushNotification.url(options),
    method: 'post',
})

testPushNotification.definition = {
    methods: ["post"],
    url: '/api/test/push-notification',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\TestController::testPushNotification
 * @see app/Http/Controllers/Api/TestController.php:16
 * @route '/api/test/push-notification'
 */
testPushNotification.url = (options?: RouteQueryOptions) => {
    return testPushNotification.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\TestController::testPushNotification
 * @see app/Http/Controllers/Api/TestController.php:16
 * @route '/api/test/push-notification'
 */
testPushNotification.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: testPushNotification.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Api\TestController::testPushNotification
 * @see app/Http/Controllers/Api/TestController.php:16
 * @route '/api/test/push-notification'
 */
    const testPushNotificationForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: testPushNotification.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Api\TestController::testPushNotification
 * @see app/Http/Controllers/Api/TestController.php:16
 * @route '/api/test/push-notification'
 */
        testPushNotificationForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: testPushNotification.url(options),
            method: 'post',
        })
    
    testPushNotification.form = testPushNotificationForm
/**
* @see \App\Http\Controllers\Api\TestController::getFcmTokenStatus
 * @see app/Http/Controllers/Api/TestController.php:50
 * @route '/api/test/fcm-token-status'
 */
export const getFcmTokenStatus = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getFcmTokenStatus.url(options),
    method: 'get',
})

getFcmTokenStatus.definition = {
    methods: ["get","head"],
    url: '/api/test/fcm-token-status',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\TestController::getFcmTokenStatus
 * @see app/Http/Controllers/Api/TestController.php:50
 * @route '/api/test/fcm-token-status'
 */
getFcmTokenStatus.url = (options?: RouteQueryOptions) => {
    return getFcmTokenStatus.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\TestController::getFcmTokenStatus
 * @see app/Http/Controllers/Api/TestController.php:50
 * @route '/api/test/fcm-token-status'
 */
getFcmTokenStatus.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getFcmTokenStatus.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Api\TestController::getFcmTokenStatus
 * @see app/Http/Controllers/Api/TestController.php:50
 * @route '/api/test/fcm-token-status'
 */
getFcmTokenStatus.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: getFcmTokenStatus.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Api\TestController::getFcmTokenStatus
 * @see app/Http/Controllers/Api/TestController.php:50
 * @route '/api/test/fcm-token-status'
 */
    const getFcmTokenStatusForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: getFcmTokenStatus.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Api\TestController::getFcmTokenStatus
 * @see app/Http/Controllers/Api/TestController.php:50
 * @route '/api/test/fcm-token-status'
 */
        getFcmTokenStatusForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getFcmTokenStatus.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Api\TestController::getFcmTokenStatus
 * @see app/Http/Controllers/Api/TestController.php:50
 * @route '/api/test/fcm-token-status'
 */
        getFcmTokenStatusForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getFcmTokenStatus.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    getFcmTokenStatus.form = getFcmTokenStatusForm
const TestController = { testPushNotification, getFcmTokenStatus }

export default TestController