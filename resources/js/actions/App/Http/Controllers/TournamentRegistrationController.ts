import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\TournamentRegistrationController::initializePayment
 * @see app/Http/Controllers/TournamentRegistrationController.php:309
 * @route '/api/tournaments/{tournament}/initialize-payment'
 */
export const initializePayment = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: initializePayment.url(args, options),
    method: 'post',
})

initializePayment.definition = {
    methods: ["post"],
    url: '/api/tournaments/{tournament}/initialize-payment',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\TournamentRegistrationController::initializePayment
 * @see app/Http/Controllers/TournamentRegistrationController.php:309
 * @route '/api/tournaments/{tournament}/initialize-payment'
 */
initializePayment.url = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return initializePayment.definition.url
            .replace('{tournament}', parsedArgs.tournament.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TournamentRegistrationController::initializePayment
 * @see app/Http/Controllers/TournamentRegistrationController.php:309
 * @route '/api/tournaments/{tournament}/initialize-payment'
 */
initializePayment.post = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: initializePayment.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\TournamentRegistrationController::initializePayment
 * @see app/Http/Controllers/TournamentRegistrationController.php:309
 * @route '/api/tournaments/{tournament}/initialize-payment'
 */
    const initializePaymentForm = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: initializePayment.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\TournamentRegistrationController::initializePayment
 * @see app/Http/Controllers/TournamentRegistrationController.php:309
 * @route '/api/tournaments/{tournament}/initialize-payment'
 */
        initializePaymentForm.post = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: initializePayment.url(args, options),
            method: 'post',
        })
    
    initializePayment.form = initializePaymentForm
/**
* @see \App\Http\Controllers\TournamentRegistrationController::checkPaymentStatus
 * @see app/Http/Controllers/TournamentRegistrationController.php:382
 * @route '/api/tournaments/{tournament}/check-payment-status'
 */
export const checkPaymentStatus = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: checkPaymentStatus.url(args, options),
    method: 'get',
})

checkPaymentStatus.definition = {
    methods: ["get","head"],
    url: '/api/tournaments/{tournament}/check-payment-status',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TournamentRegistrationController::checkPaymentStatus
 * @see app/Http/Controllers/TournamentRegistrationController.php:382
 * @route '/api/tournaments/{tournament}/check-payment-status'
 */
checkPaymentStatus.url = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return checkPaymentStatus.definition.url
            .replace('{tournament}', parsedArgs.tournament.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TournamentRegistrationController::checkPaymentStatus
 * @see app/Http/Controllers/TournamentRegistrationController.php:382
 * @route '/api/tournaments/{tournament}/check-payment-status'
 */
checkPaymentStatus.get = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: checkPaymentStatus.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\TournamentRegistrationController::checkPaymentStatus
 * @see app/Http/Controllers/TournamentRegistrationController.php:382
 * @route '/api/tournaments/{tournament}/check-payment-status'
 */
checkPaymentStatus.head = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: checkPaymentStatus.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\TournamentRegistrationController::checkPaymentStatus
 * @see app/Http/Controllers/TournamentRegistrationController.php:382
 * @route '/api/tournaments/{tournament}/check-payment-status'
 */
    const checkPaymentStatusForm = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: checkPaymentStatus.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\TournamentRegistrationController::checkPaymentStatus
 * @see app/Http/Controllers/TournamentRegistrationController.php:382
 * @route '/api/tournaments/{tournament}/check-payment-status'
 */
        checkPaymentStatusForm.get = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: checkPaymentStatus.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\TournamentRegistrationController::checkPaymentStatus
 * @see app/Http/Controllers/TournamentRegistrationController.php:382
 * @route '/api/tournaments/{tournament}/check-payment-status'
 */
        checkPaymentStatusForm.head = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: checkPaymentStatus.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    checkPaymentStatus.form = checkPaymentStatusForm
/**
* @see \App\Http\Controllers\TournamentRegistrationController::testTinyPesa
 * @see app/Http/Controllers/TournamentRegistrationController.php:354
 * @route '/api/test-tinypesa'
 */
export const testTinyPesa = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: testTinyPesa.url(options),
    method: 'get',
})

testTinyPesa.definition = {
    methods: ["get","head"],
    url: '/api/test-tinypesa',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TournamentRegistrationController::testTinyPesa
 * @see app/Http/Controllers/TournamentRegistrationController.php:354
 * @route '/api/test-tinypesa'
 */
testTinyPesa.url = (options?: RouteQueryOptions) => {
    return testTinyPesa.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TournamentRegistrationController::testTinyPesa
 * @see app/Http/Controllers/TournamentRegistrationController.php:354
 * @route '/api/test-tinypesa'
 */
testTinyPesa.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: testTinyPesa.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\TournamentRegistrationController::testTinyPesa
 * @see app/Http/Controllers/TournamentRegistrationController.php:354
 * @route '/api/test-tinypesa'
 */
testTinyPesa.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: testTinyPesa.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\TournamentRegistrationController::testTinyPesa
 * @see app/Http/Controllers/TournamentRegistrationController.php:354
 * @route '/api/test-tinypesa'
 */
    const testTinyPesaForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: testTinyPesa.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\TournamentRegistrationController::testTinyPesa
 * @see app/Http/Controllers/TournamentRegistrationController.php:354
 * @route '/api/test-tinypesa'
 */
        testTinyPesaForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: testTinyPesa.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\TournamentRegistrationController::testTinyPesa
 * @see app/Http/Controllers/TournamentRegistrationController.php:354
 * @route '/api/test-tinypesa'
 */
        testTinyPesaForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: testTinyPesa.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    testTinyPesa.form = testTinyPesaForm
/**
* @see \App\Http\Controllers\TournamentRegistrationController::available
 * @see app/Http/Controllers/TournamentRegistrationController.php:25
 * @route '/api/tournaments/available'
 */
export const available = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: available.url(options),
    method: 'get',
})

available.definition = {
    methods: ["get","head"],
    url: '/api/tournaments/available',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TournamentRegistrationController::available
 * @see app/Http/Controllers/TournamentRegistrationController.php:25
 * @route '/api/tournaments/available'
 */
available.url = (options?: RouteQueryOptions) => {
    return available.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TournamentRegistrationController::available
 * @see app/Http/Controllers/TournamentRegistrationController.php:25
 * @route '/api/tournaments/available'
 */
available.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: available.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\TournamentRegistrationController::available
 * @see app/Http/Controllers/TournamentRegistrationController.php:25
 * @route '/api/tournaments/available'
 */
available.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: available.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\TournamentRegistrationController::available
 * @see app/Http/Controllers/TournamentRegistrationController.php:25
 * @route '/api/tournaments/available'
 */
    const availableForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: available.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\TournamentRegistrationController::available
 * @see app/Http/Controllers/TournamentRegistrationController.php:25
 * @route '/api/tournaments/available'
 */
        availableForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: available.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\TournamentRegistrationController::available
 * @see app/Http/Controllers/TournamentRegistrationController.php:25
 * @route '/api/tournaments/available'
 */
        availableForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: available.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    available.form = availableForm
/**
* @see \App\Http\Controllers\TournamentRegistrationController::confirmPayment
 * @see app/Http/Controllers/TournamentRegistrationController.php:143
 * @route '/api/tournaments/{tournament}/confirm-payment'
 */
export const confirmPayment = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirmPayment.url(args, options),
    method: 'post',
})

confirmPayment.definition = {
    methods: ["post"],
    url: '/api/tournaments/{tournament}/confirm-payment',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\TournamentRegistrationController::confirmPayment
 * @see app/Http/Controllers/TournamentRegistrationController.php:143
 * @route '/api/tournaments/{tournament}/confirm-payment'
 */
confirmPayment.url = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return confirmPayment.definition.url
            .replace('{tournament}', parsedArgs.tournament.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TournamentRegistrationController::confirmPayment
 * @see app/Http/Controllers/TournamentRegistrationController.php:143
 * @route '/api/tournaments/{tournament}/confirm-payment'
 */
confirmPayment.post = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirmPayment.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\TournamentRegistrationController::confirmPayment
 * @see app/Http/Controllers/TournamentRegistrationController.php:143
 * @route '/api/tournaments/{tournament}/confirm-payment'
 */
    const confirmPaymentForm = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: confirmPayment.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\TournamentRegistrationController::confirmPayment
 * @see app/Http/Controllers/TournamentRegistrationController.php:143
 * @route '/api/tournaments/{tournament}/confirm-payment'
 */
        confirmPaymentForm.post = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: confirmPayment.url(args, options),
            method: 'post',
        })
    
    confirmPayment.form = confirmPaymentForm
/**
* @see \App\Http\Controllers\TournamentRegistrationController::cancel
 * @see app/Http/Controllers/TournamentRegistrationController.php:196
 * @route '/api/tournaments/{tournament}/cancel'
 */
export const cancel = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: cancel.url(args, options),
    method: 'delete',
})

cancel.definition = {
    methods: ["delete"],
    url: '/api/tournaments/{tournament}/cancel',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\TournamentRegistrationController::cancel
 * @see app/Http/Controllers/TournamentRegistrationController.php:196
 * @route '/api/tournaments/{tournament}/cancel'
 */
cancel.url = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return cancel.definition.url
            .replace('{tournament}', parsedArgs.tournament.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TournamentRegistrationController::cancel
 * @see app/Http/Controllers/TournamentRegistrationController.php:196
 * @route '/api/tournaments/{tournament}/cancel'
 */
cancel.delete = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: cancel.url(args, options),
    method: 'delete',
})

    /**
* @see \App\Http\Controllers\TournamentRegistrationController::cancel
 * @see app/Http/Controllers/TournamentRegistrationController.php:196
 * @route '/api/tournaments/{tournament}/cancel'
 */
    const cancelForm = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: cancel.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'DELETE',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\TournamentRegistrationController::cancel
 * @see app/Http/Controllers/TournamentRegistrationController.php:196
 * @route '/api/tournaments/{tournament}/cancel'
 */
        cancelForm.delete = (args: { tournament: string | number } | [tournament: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: cancel.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'DELETE',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    cancel.form = cancelForm
const TournamentRegistrationController = { initializePayment, checkPaymentStatus, testTinyPesa, available, confirmPayment, cancel }

export default TournamentRegistrationController