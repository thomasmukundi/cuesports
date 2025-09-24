import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\ContactSupportController::store
 * @see app/Http/Controllers/Api/ContactSupportController.php:15
 * @route '/api/contact-support'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/api/contact-support',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\ContactSupportController::store
 * @see app/Http/Controllers/Api/ContactSupportController.php:15
 * @route '/api/contact-support'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\ContactSupportController::store
 * @see app/Http/Controllers/Api/ContactSupportController.php:15
 * @route '/api/contact-support'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Api\ContactSupportController::store
 * @see app/Http/Controllers/Api/ContactSupportController.php:15
 * @route '/api/contact-support'
 */
    const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: store.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Api\ContactSupportController::store
 * @see app/Http/Controllers/Api/ContactSupportController.php:15
 * @route '/api/contact-support'
 */
        storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: store.url(options),
            method: 'post',
        })
    
    store.form = storeForm
/**
* @see \App\Http\Controllers\Api\ContactSupportController::index
 * @see app/Http/Controllers/Api/ContactSupportController.php:65
 * @route '/api/contact-support'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/contact-support',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\ContactSupportController::index
 * @see app/Http/Controllers/Api/ContactSupportController.php:65
 * @route '/api/contact-support'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\ContactSupportController::index
 * @see app/Http/Controllers/Api/ContactSupportController.php:65
 * @route '/api/contact-support'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Api\ContactSupportController::index
 * @see app/Http/Controllers/Api/ContactSupportController.php:65
 * @route '/api/contact-support'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Api\ContactSupportController::index
 * @see app/Http/Controllers/Api/ContactSupportController.php:65
 * @route '/api/contact-support'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Api\ContactSupportController::index
 * @see app/Http/Controllers/Api/ContactSupportController.php:65
 * @route '/api/contact-support'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Api\ContactSupportController::index
 * @see app/Http/Controllers/Api/ContactSupportController.php:65
 * @route '/api/contact-support'
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
const ContactSupportController = { store, index }

export default ContactSupportController