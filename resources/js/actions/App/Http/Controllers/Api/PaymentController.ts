import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\PaymentController::processTournamentPayment
 * @see app/Http/Controllers/Api/PaymentController.php:16
 * @route '/api/tournaments/{tournament}/pay'
 */
export const processTournamentPayment = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: processTournamentPayment.url(args, options),
    method: 'post',
})

processTournamentPayment.definition = {
    methods: ["post"],
    url: '/api/tournaments/{tournament}/pay',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\PaymentController::processTournamentPayment
 * @see app/Http/Controllers/Api/PaymentController.php:16
 * @route '/api/tournaments/{tournament}/pay'
 */
processTournamentPayment.url = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return processTournamentPayment.definition.url
            .replace('{tournament}', parsedArgs.tournament.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\PaymentController::processTournamentPayment
 * @see app/Http/Controllers/Api/PaymentController.php:16
 * @route '/api/tournaments/{tournament}/pay'
 */
processTournamentPayment.post = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: processTournamentPayment.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Api\PaymentController::processTournamentPayment
 * @see app/Http/Controllers/Api/PaymentController.php:16
 * @route '/api/tournaments/{tournament}/pay'
 */
    const processTournamentPaymentForm = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: processTournamentPayment.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Api\PaymentController::processTournamentPayment
 * @see app/Http/Controllers/Api/PaymentController.php:16
 * @route '/api/tournaments/{tournament}/pay'
 */
        processTournamentPaymentForm.post = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: processTournamentPayment.url(args, options),
            method: 'post',
        })
    
    processTournamentPayment.form = processTournamentPaymentForm
/**
* @see \App\Http\Controllers\Api\PaymentController::getPaymentStatus
 * @see app/Http/Controllers/Api/PaymentController.php:56
 * @route '/api/payments/{payment}/status'
 */
export const getPaymentStatus = (args: { payment: string | number } | [payment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getPaymentStatus.url(args, options),
    method: 'get',
})

getPaymentStatus.definition = {
    methods: ["get","head"],
    url: '/api/payments/{payment}/status',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\PaymentController::getPaymentStatus
 * @see app/Http/Controllers/Api/PaymentController.php:56
 * @route '/api/payments/{payment}/status'
 */
getPaymentStatus.url = (args: { payment: string | number } | [payment: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { payment: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    payment: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        payment: args.payment,
                }

    return getPaymentStatus.definition.url
            .replace('{payment}', parsedArgs.payment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\PaymentController::getPaymentStatus
 * @see app/Http/Controllers/Api/PaymentController.php:56
 * @route '/api/payments/{payment}/status'
 */
getPaymentStatus.get = (args: { payment: string | number } | [payment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getPaymentStatus.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Api\PaymentController::getPaymentStatus
 * @see app/Http/Controllers/Api/PaymentController.php:56
 * @route '/api/payments/{payment}/status'
 */
getPaymentStatus.head = (args: { payment: string | number } | [payment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: getPaymentStatus.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Api\PaymentController::getPaymentStatus
 * @see app/Http/Controllers/Api/PaymentController.php:56
 * @route '/api/payments/{payment}/status'
 */
    const getPaymentStatusForm = (args: { payment: string | number } | [payment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: getPaymentStatus.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Api\PaymentController::getPaymentStatus
 * @see app/Http/Controllers/Api/PaymentController.php:56
 * @route '/api/payments/{payment}/status'
 */
        getPaymentStatusForm.get = (args: { payment: string | number } | [payment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getPaymentStatus.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Api\PaymentController::getPaymentStatus
 * @see app/Http/Controllers/Api/PaymentController.php:56
 * @route '/api/payments/{payment}/status'
 */
        getPaymentStatusForm.head = (args: { payment: string | number } | [payment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getPaymentStatus.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    getPaymentStatus.form = getPaymentStatusForm
/**
* @see \App\Http\Controllers\Api\PaymentController::getPaymentHistory
 * @see app/Http/Controllers/Api/PaymentController.php:76
 * @route '/api/payments/history'
 */
export const getPaymentHistory = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getPaymentHistory.url(options),
    method: 'get',
})

getPaymentHistory.definition = {
    methods: ["get","head"],
    url: '/api/payments/history',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\PaymentController::getPaymentHistory
 * @see app/Http/Controllers/Api/PaymentController.php:76
 * @route '/api/payments/history'
 */
getPaymentHistory.url = (options?: RouteQueryOptions) => {
    return getPaymentHistory.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\PaymentController::getPaymentHistory
 * @see app/Http/Controllers/Api/PaymentController.php:76
 * @route '/api/payments/history'
 */
getPaymentHistory.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getPaymentHistory.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Api\PaymentController::getPaymentHistory
 * @see app/Http/Controllers/Api/PaymentController.php:76
 * @route '/api/payments/history'
 */
getPaymentHistory.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: getPaymentHistory.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Api\PaymentController::getPaymentHistory
 * @see app/Http/Controllers/Api/PaymentController.php:76
 * @route '/api/payments/history'
 */
    const getPaymentHistoryForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: getPaymentHistory.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Api\PaymentController::getPaymentHistory
 * @see app/Http/Controllers/Api/PaymentController.php:76
 * @route '/api/payments/history'
 */
        getPaymentHistoryForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getPaymentHistory.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Api\PaymentController::getPaymentHistory
 * @see app/Http/Controllers/Api/PaymentController.php:76
 * @route '/api/payments/history'
 */
        getPaymentHistoryForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getPaymentHistory.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    getPaymentHistory.form = getPaymentHistoryForm
const PaymentController = { processTournamentPayment, getPaymentStatus, getPaymentHistory }

export default PaymentController