import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\NotificationController::index
 * @see app/Http/Controllers/Api/NotificationController.php:15
 * @route '/api/notifications'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/notifications',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\NotificationController::index
 * @see app/Http/Controllers/Api/NotificationController.php:15
 * @route '/api/notifications'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\NotificationController::index
 * @see app/Http/Controllers/Api/NotificationController.php:15
 * @route '/api/notifications'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Api\NotificationController::index
 * @see app/Http/Controllers/Api/NotificationController.php:15
 * @route '/api/notifications'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Api\NotificationController::index
 * @see app/Http/Controllers/Api/NotificationController.php:15
 * @route '/api/notifications'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Api\NotificationController::index
 * @see app/Http/Controllers/Api/NotificationController.php:15
 * @route '/api/notifications'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Api\NotificationController::index
 * @see app/Http/Controllers/Api/NotificationController.php:15
 * @route '/api/notifications'
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
* @see \App\Http\Controllers\Api\NotificationController::unreadCount
 * @see app/Http/Controllers/Api/NotificationController.php:74
 * @route '/api/notifications/unread-count'
 */
export const unreadCount = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: unreadCount.url(options),
    method: 'get',
})

unreadCount.definition = {
    methods: ["get","head"],
    url: '/api/notifications/unread-count',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\NotificationController::unreadCount
 * @see app/Http/Controllers/Api/NotificationController.php:74
 * @route '/api/notifications/unread-count'
 */
unreadCount.url = (options?: RouteQueryOptions) => {
    return unreadCount.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\NotificationController::unreadCount
 * @see app/Http/Controllers/Api/NotificationController.php:74
 * @route '/api/notifications/unread-count'
 */
unreadCount.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: unreadCount.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Api\NotificationController::unreadCount
 * @see app/Http/Controllers/Api/NotificationController.php:74
 * @route '/api/notifications/unread-count'
 */
unreadCount.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: unreadCount.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Api\NotificationController::unreadCount
 * @see app/Http/Controllers/Api/NotificationController.php:74
 * @route '/api/notifications/unread-count'
 */
    const unreadCountForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: unreadCount.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Api\NotificationController::unreadCount
 * @see app/Http/Controllers/Api/NotificationController.php:74
 * @route '/api/notifications/unread-count'
 */
        unreadCountForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: unreadCount.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Api\NotificationController::unreadCount
 * @see app/Http/Controllers/Api/NotificationController.php:74
 * @route '/api/notifications/unread-count'
 */
        unreadCountForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: unreadCount.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    unreadCount.form = unreadCountForm
/**
* @see \App\Http\Controllers\Api\NotificationController::realTimeCheck
 * @see app/Http/Controllers/Api/NotificationController.php:170
 * @route '/api/notifications/real-time-check'
 */
export const realTimeCheck = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: realTimeCheck.url(options),
    method: 'get',
})

realTimeCheck.definition = {
    methods: ["get","head"],
    url: '/api/notifications/real-time-check',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\NotificationController::realTimeCheck
 * @see app/Http/Controllers/Api/NotificationController.php:170
 * @route '/api/notifications/real-time-check'
 */
realTimeCheck.url = (options?: RouteQueryOptions) => {
    return realTimeCheck.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\NotificationController::realTimeCheck
 * @see app/Http/Controllers/Api/NotificationController.php:170
 * @route '/api/notifications/real-time-check'
 */
realTimeCheck.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: realTimeCheck.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Api\NotificationController::realTimeCheck
 * @see app/Http/Controllers/Api/NotificationController.php:170
 * @route '/api/notifications/real-time-check'
 */
realTimeCheck.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: realTimeCheck.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Api\NotificationController::realTimeCheck
 * @see app/Http/Controllers/Api/NotificationController.php:170
 * @route '/api/notifications/real-time-check'
 */
    const realTimeCheckForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: realTimeCheck.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Api\NotificationController::realTimeCheck
 * @see app/Http/Controllers/Api/NotificationController.php:170
 * @route '/api/notifications/real-time-check'
 */
        realTimeCheckForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: realTimeCheck.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Api\NotificationController::realTimeCheck
 * @see app/Http/Controllers/Api/NotificationController.php:170
 * @route '/api/notifications/real-time-check'
 */
        realTimeCheckForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: realTimeCheck.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    realTimeCheck.form = realTimeCheckForm
/**
* @see \App\Http\Controllers\Api\NotificationController::markAsRead
 * @see app/Http/Controllers/Api/NotificationController.php:91
 * @route '/api/notifications/{notification}/read'
 */
export const markAsRead = (args: { notification: number | { id: number } } | [notification: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: markAsRead.url(args, options),
    method: 'post',
})

markAsRead.definition = {
    methods: ["post"],
    url: '/api/notifications/{notification}/read',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\NotificationController::markAsRead
 * @see app/Http/Controllers/Api/NotificationController.php:91
 * @route '/api/notifications/{notification}/read'
 */
markAsRead.url = (args: { notification: number | { id: number } } | [notification: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { notification: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { notification: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    notification: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        notification: typeof args.notification === 'object'
                ? args.notification.id
                : args.notification,
                }

    return markAsRead.definition.url
            .replace('{notification}', parsedArgs.notification.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\NotificationController::markAsRead
 * @see app/Http/Controllers/Api/NotificationController.php:91
 * @route '/api/notifications/{notification}/read'
 */
markAsRead.post = (args: { notification: number | { id: number } } | [notification: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: markAsRead.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Api\NotificationController::markAsRead
 * @see app/Http/Controllers/Api/NotificationController.php:91
 * @route '/api/notifications/{notification}/read'
 */
    const markAsReadForm = (args: { notification: number | { id: number } } | [notification: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: markAsRead.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Api\NotificationController::markAsRead
 * @see app/Http/Controllers/Api/NotificationController.php:91
 * @route '/api/notifications/{notification}/read'
 */
        markAsReadForm.post = (args: { notification: number | { id: number } } | [notification: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: markAsRead.url(args, options),
            method: 'post',
        })
    
    markAsRead.form = markAsReadForm
/**
* @see \App\Http\Controllers\Api\NotificationController::markAllAsRead
 * @see app/Http/Controllers/Api/NotificationController.php:114
 * @route '/api/notifications/mark-all-read'
 */
export const markAllAsRead = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: markAllAsRead.url(options),
    method: 'post',
})

markAllAsRead.definition = {
    methods: ["post"],
    url: '/api/notifications/mark-all-read',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\NotificationController::markAllAsRead
 * @see app/Http/Controllers/Api/NotificationController.php:114
 * @route '/api/notifications/mark-all-read'
 */
markAllAsRead.url = (options?: RouteQueryOptions) => {
    return markAllAsRead.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\NotificationController::markAllAsRead
 * @see app/Http/Controllers/Api/NotificationController.php:114
 * @route '/api/notifications/mark-all-read'
 */
markAllAsRead.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: markAllAsRead.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Api\NotificationController::markAllAsRead
 * @see app/Http/Controllers/Api/NotificationController.php:114
 * @route '/api/notifications/mark-all-read'
 */
    const markAllAsReadForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: markAllAsRead.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Api\NotificationController::markAllAsRead
 * @see app/Http/Controllers/Api/NotificationController.php:114
 * @route '/api/notifications/mark-all-read'
 */
        markAllAsReadForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: markAllAsRead.url(options),
            method: 'post',
        })
    
    markAllAsRead.form = markAllAsReadForm
/**
* @see \App\Http\Controllers\Api\NotificationController::clearAll
 * @see app/Http/Controllers/Api/NotificationController.php:154
 * @route '/api/notifications/clear-all'
 */
export const clearAll = (options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: clearAll.url(options),
    method: 'delete',
})

clearAll.definition = {
    methods: ["delete"],
    url: '/api/notifications/clear-all',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\Api\NotificationController::clearAll
 * @see app/Http/Controllers/Api/NotificationController.php:154
 * @route '/api/notifications/clear-all'
 */
clearAll.url = (options?: RouteQueryOptions) => {
    return clearAll.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\NotificationController::clearAll
 * @see app/Http/Controllers/Api/NotificationController.php:154
 * @route '/api/notifications/clear-all'
 */
clearAll.delete = (options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: clearAll.url(options),
    method: 'delete',
})

    /**
* @see \App\Http\Controllers\Api\NotificationController::clearAll
 * @see app/Http/Controllers/Api/NotificationController.php:154
 * @route '/api/notifications/clear-all'
 */
    const clearAllForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: clearAll.url({
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'DELETE',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Api\NotificationController::clearAll
 * @see app/Http/Controllers/Api/NotificationController.php:154
 * @route '/api/notifications/clear-all'
 */
        clearAllForm.delete = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: clearAll.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'DELETE',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    clearAll.form = clearAllForm
const NotificationController = { index, unreadCount, realTimeCheck, markAsRead, markAllAsRead, clearAll }

export default NotificationController