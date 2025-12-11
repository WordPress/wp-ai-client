import type { ProviderMetadata, ModelMetadata } from '../../types';
import type { ProvidersModelsState } from './types';

const EMPTY_MODELS_ARRAY: ModelMetadata[] = [];

/**
 * Returns all registered AI providers.
 *
 * @since n.e.x.t
 *
 * @param state Store state.
 * @return Array of providers.
 */
export const getProviders = (
	state: ProvidersModelsState
): ProviderMetadata[] => {
	return state.providers;
};

/**
 * Returns a specific provider by its ID.
 *
 * @since n.e.x.t
 *
 * @param state Store state.
 * @param id    Provider ID.
 * @return Provider object, or undefined if not found.
 */
export function getProvider(
	state: ProvidersModelsState,
	id: string
): ProviderMetadata | undefined {
	if ( ! ( id in state.providerLookupMap ) ) {
		return undefined;
	}

	const index = state.providerLookupMap[ id ];
	return state.providers[ index ];
}

/**
 * Returns all models for a specific provider.
 *
 * @since n.e.x.t
 *
 * @param state      Store state.
 * @param providerId Provider ID.
 * @return Array of models for the provider.
 */
export const getProviderModels = (
	state: ProvidersModelsState,
	providerId: string
): ModelMetadata[] => {
	return state.modelsByProvider[ providerId ] || EMPTY_MODELS_ARRAY;
};

/**
 * Returns a specific model by its ID for a provider.
 *
 * @since n.e.x.t
 *
 * @param state      Store state.
 * @param providerId Provider ID.
 * @param modelId    Model ID.
 * @return Model object, or undefined if not found.
 */
export function getProviderModel(
	state: ProvidersModelsState,
	providerId: string,
	modelId: string
): ModelMetadata | undefined {
	if (
		! ( providerId in state.providerModelsLookupMap ) ||
		! ( modelId in state.providerModelsLookupMap[ providerId ] )
	) {
		return undefined;
	}

	const index = state.providerModelsLookupMap[ providerId ][ modelId ];
	return state.modelsByProvider[ providerId ][ index ];
}
