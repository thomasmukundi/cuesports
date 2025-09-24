import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\TournamentProgressionController::checkRoundCompletion
 * @see app/Http/Controllers/Api/TournamentProgressionController.php:25
 * @route '/api/tournament-progression/check-round-completion'
 */
export const checkRoundCompletion = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: checkRoundCompletion.url(options),
    method: 'post',
})

checkRoundCompletion.definition = {
    methods: ["post"],
    url: '/api/tournament-progression/check-round-completion',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\TournamentProgressionController::checkRoundCompletion
 * @see app/Http/Controllers/Api/TournamentProgressionController.php:25
 * @route '/api/tournament-progression/check-round-completion'
 */
checkRoundCompletion.url = (options?: RouteQueryOptions) => {
    return checkRoundCompletion.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\TournamentProgressionController::checkRoundCompletion
 * @see app/Http/Controllers/Api/TournamentProgressionController.php:25
 * @route '/api/tournament-progression/check-round-completion'
 */
checkRoundCompletion.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: checkRoundCompletion.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Api\TournamentProgressionController::checkRoundCompletion
 * @see app/Http/Controllers/Api/TournamentProgressionController.php:25
 * @route '/api/tournament-progression/check-round-completion'
 */
    const checkRoundCompletionForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: checkRoundCompletion.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Api\TournamentProgressionController::checkRoundCompletion
 * @see app/Http/Controllers/Api/TournamentProgressionController.php:25
 * @route '/api/tournament-progression/check-round-completion'
 */
        checkRoundCompletionForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: checkRoundCompletion.url(options),
            method: 'post',
        })
    
    checkRoundCompletion.form = checkRoundCompletionForm
/**
* @see \App\Http\Controllers\Api\TournamentProgressionController::determinePositions
 * @see app/Http/Controllers/Api/TournamentProgressionController.php:0
 * @route '/api/tournament-progression/determine-positions'
 */
export const determinePositions = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: determinePositions.url(options),
    method: 'post',
})

determinePositions.definition = {
    methods: ["post"],
    url: '/api/tournament-progression/determine-positions',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\TournamentProgressionController::determinePositions
 * @see app/Http/Controllers/Api/TournamentProgressionController.php:0
 * @route '/api/tournament-progression/determine-positions'
 */
determinePositions.url = (options?: RouteQueryOptions) => {
    return determinePositions.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\TournamentProgressionController::determinePositions
 * @see app/Http/Controllers/Api/TournamentProgressionController.php:0
 * @route '/api/tournament-progression/determine-positions'
 */
determinePositions.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: determinePositions.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Api\TournamentProgressionController::determinePositions
 * @see app/Http/Controllers/Api/TournamentProgressionController.php:0
 * @route '/api/tournament-progression/determine-positions'
 */
    const determinePositionsForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: determinePositions.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Api\TournamentProgressionController::determinePositions
 * @see app/Http/Controllers/Api/TournamentProgressionController.php:0
 * @route '/api/tournament-progression/determine-positions'
 */
        determinePositionsForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: determinePositions.url(options),
            method: 'post',
        })
    
    determinePositions.form = determinePositionsForm
const TournamentProgressionController = { checkRoundCompletion, determinePositions }

export default TournamentProgressionController