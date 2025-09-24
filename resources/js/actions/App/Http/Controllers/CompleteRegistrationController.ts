import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\CompleteRegistrationController::completeRegistration
 * @see app/Http/Controllers/CompleteRegistrationController.php:16
 * @route '/api/complete-registration'
 */
export const completeRegistration = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: completeRegistration.url(options),
    method: 'post',
})

completeRegistration.definition = {
    methods: ["post"],
    url: '/api/complete-registration',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CompleteRegistrationController::completeRegistration
 * @see app/Http/Controllers/CompleteRegistrationController.php:16
 * @route '/api/complete-registration'
 */
completeRegistration.url = (options?: RouteQueryOptions) => {
    return completeRegistration.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CompleteRegistrationController::completeRegistration
 * @see app/Http/Controllers/CompleteRegistrationController.php:16
 * @route '/api/complete-registration'
 */
completeRegistration.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: completeRegistration.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\CompleteRegistrationController::completeRegistration
 * @see app/Http/Controllers/CompleteRegistrationController.php:16
 * @route '/api/complete-registration'
 */
    const completeRegistrationForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: completeRegistration.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\CompleteRegistrationController::completeRegistration
 * @see app/Http/Controllers/CompleteRegistrationController.php:16
 * @route '/api/complete-registration'
 */
        completeRegistrationForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: completeRegistration.url(options),
            method: 'post',
        })
    
    completeRegistration.form = completeRegistrationForm
const CompleteRegistrationController = { completeRegistration }

export default CompleteRegistrationController