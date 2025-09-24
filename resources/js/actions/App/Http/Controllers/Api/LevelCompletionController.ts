import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\LevelCompletionController::checkLevelCompletion
 * @see app/Http/Controllers/Api/LevelCompletionController.php:27
 * @route '/api/level-completion/check'
 */
export const checkLevelCompletion = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: checkLevelCompletion.url(options),
    method: 'post',
})

checkLevelCompletion.definition = {
    methods: ["post"],
    url: '/api/level-completion/check',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\LevelCompletionController::checkLevelCompletion
 * @see app/Http/Controllers/Api/LevelCompletionController.php:27
 * @route '/api/level-completion/check'
 */
checkLevelCompletion.url = (options?: RouteQueryOptions) => {
    return checkLevelCompletion.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\LevelCompletionController::checkLevelCompletion
 * @see app/Http/Controllers/Api/LevelCompletionController.php:27
 * @route '/api/level-completion/check'
 */
checkLevelCompletion.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: checkLevelCompletion.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Api\LevelCompletionController::checkLevelCompletion
 * @see app/Http/Controllers/Api/LevelCompletionController.php:27
 * @route '/api/level-completion/check'
 */
    const checkLevelCompletionForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: checkLevelCompletion.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Api\LevelCompletionController::checkLevelCompletion
 * @see app/Http/Controllers/Api/LevelCompletionController.php:27
 * @route '/api/level-completion/check'
 */
        checkLevelCompletionForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: checkLevelCompletion.url(options),
            method: 'post',
        })
    
    checkLevelCompletion.form = checkLevelCompletionForm
/**
* @see \App\Http\Controllers\Api\LevelCompletionController::initializeNextLevel
 * @see app/Http/Controllers/Api/LevelCompletionController.php:52
 * @route '/api/level-completion/initialize-next'
 */
export const initializeNextLevel = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: initializeNextLevel.url(options),
    method: 'post',
})

initializeNextLevel.definition = {
    methods: ["post"],
    url: '/api/level-completion/initialize-next',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\LevelCompletionController::initializeNextLevel
 * @see app/Http/Controllers/Api/LevelCompletionController.php:52
 * @route '/api/level-completion/initialize-next'
 */
initializeNextLevel.url = (options?: RouteQueryOptions) => {
    return initializeNextLevel.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\LevelCompletionController::initializeNextLevel
 * @see app/Http/Controllers/Api/LevelCompletionController.php:52
 * @route '/api/level-completion/initialize-next'
 */
initializeNextLevel.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: initializeNextLevel.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Api\LevelCompletionController::initializeNextLevel
 * @see app/Http/Controllers/Api/LevelCompletionController.php:52
 * @route '/api/level-completion/initialize-next'
 */
    const initializeNextLevelForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: initializeNextLevel.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Api\LevelCompletionController::initializeNextLevel
 * @see app/Http/Controllers/Api/LevelCompletionController.php:52
 * @route '/api/level-completion/initialize-next'
 */
        initializeNextLevelForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: initializeNextLevel.url(options),
            method: 'post',
        })
    
    initializeNextLevel.form = initializeNextLevelForm
const LevelCompletionController = { checkLevelCompletion, initializeNextLevel }

export default LevelCompletionController