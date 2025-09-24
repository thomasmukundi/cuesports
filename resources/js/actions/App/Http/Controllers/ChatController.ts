import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\ChatController::getMatchMessages
 * @see app/Http/Controllers/ChatController.php:17
 * @route '/api/chat/match/{match}'
 */
export const getMatchMessages = (args: { match: string | number } | [match: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getMatchMessages.url(args, options),
    method: 'get',
})

getMatchMessages.definition = {
    methods: ["get","head"],
    url: '/api/chat/match/{match}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ChatController::getMatchMessages
 * @see app/Http/Controllers/ChatController.php:17
 * @route '/api/chat/match/{match}'
 */
getMatchMessages.url = (args: { match: string | number } | [match: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return getMatchMessages.definition.url
            .replace('{match}', parsedArgs.match.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ChatController::getMatchMessages
 * @see app/Http/Controllers/ChatController.php:17
 * @route '/api/chat/match/{match}'
 */
getMatchMessages.get = (args: { match: string | number } | [match: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getMatchMessages.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ChatController::getMatchMessages
 * @see app/Http/Controllers/ChatController.php:17
 * @route '/api/chat/match/{match}'
 */
getMatchMessages.head = (args: { match: string | number } | [match: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: getMatchMessages.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\ChatController::getMatchMessages
 * @see app/Http/Controllers/ChatController.php:17
 * @route '/api/chat/match/{match}'
 */
    const getMatchMessagesForm = (args: { match: string | number } | [match: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: getMatchMessages.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\ChatController::getMatchMessages
 * @see app/Http/Controllers/ChatController.php:17
 * @route '/api/chat/match/{match}'
 */
        getMatchMessagesForm.get = (args: { match: string | number } | [match: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getMatchMessages.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\ChatController::getMatchMessages
 * @see app/Http/Controllers/ChatController.php:17
 * @route '/api/chat/match/{match}'
 */
        getMatchMessagesForm.head = (args: { match: string | number } | [match: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getMatchMessages.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    getMatchMessages.form = getMatchMessagesForm
/**
* @see \App\Http\Controllers\ChatController::getConversations
 * @see app/Http/Controllers/ChatController.php:94
 * @route '/api/chat/conversations'
 */
export const getConversations = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getConversations.url(options),
    method: 'get',
})

getConversations.definition = {
    methods: ["get","head"],
    url: '/api/chat/conversations',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ChatController::getConversations
 * @see app/Http/Controllers/ChatController.php:94
 * @route '/api/chat/conversations'
 */
getConversations.url = (options?: RouteQueryOptions) => {
    return getConversations.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ChatController::getConversations
 * @see app/Http/Controllers/ChatController.php:94
 * @route '/api/chat/conversations'
 */
getConversations.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getConversations.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ChatController::getConversations
 * @see app/Http/Controllers/ChatController.php:94
 * @route '/api/chat/conversations'
 */
getConversations.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: getConversations.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\ChatController::getConversations
 * @see app/Http/Controllers/ChatController.php:94
 * @route '/api/chat/conversations'
 */
    const getConversationsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: getConversations.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\ChatController::getConversations
 * @see app/Http/Controllers/ChatController.php:94
 * @route '/api/chat/conversations'
 */
        getConversationsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getConversations.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\ChatController::getConversations
 * @see app/Http/Controllers/ChatController.php:94
 * @route '/api/chat/conversations'
 */
        getConversationsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getConversations.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    getConversations.form = getConversationsForm
const ChatController = { getMatchMessages, getConversations }

export default ChatController