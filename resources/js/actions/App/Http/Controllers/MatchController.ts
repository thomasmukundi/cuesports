import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\MatchController::sendMessage
 * @see app/Http/Controllers/MatchController.php:0
 * @route '/api/chat/matches/{match}/messages'
 */
export const sendMessage = (args: { match: string | number } | [match: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: sendMessage.url(args, options),
    method: 'post',
})

sendMessage.definition = {
    methods: ["post"],
    url: '/api/chat/matches/{match}/messages',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MatchController::sendMessage
 * @see app/Http/Controllers/MatchController.php:0
 * @route '/api/chat/matches/{match}/messages'
 */
sendMessage.url = (args: { match: string | number } | [match: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return sendMessage.definition.url
            .replace('{match}', parsedArgs.match.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MatchController::sendMessage
 * @see app/Http/Controllers/MatchController.php:0
 * @route '/api/chat/matches/{match}/messages'
 */
sendMessage.post = (args: { match: string | number } | [match: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: sendMessage.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\MatchController::sendMessage
 * @see app/Http/Controllers/MatchController.php:0
 * @route '/api/chat/matches/{match}/messages'
 */
    const sendMessageForm = (args: { match: string | number } | [match: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: sendMessage.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\MatchController::sendMessage
 * @see app/Http/Controllers/MatchController.php:0
 * @route '/api/chat/matches/{match}/messages'
 */
        sendMessageForm.post = (args: { match: string | number } | [match: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: sendMessage.url(args, options),
            method: 'post',
        })
    
    sendMessage.form = sendMessageForm
const MatchController = { sendMessage }

export default MatchController