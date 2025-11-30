import type { ProviderMetadata, ModelMetadata } from '../../types';

export const RECEIVE_PROVIDERS = 'RECEIVE_PROVIDERS' as const;
export const RECEIVE_PROVIDER_MODELS = 'RECEIVE_PROVIDER_MODELS' as const;

/**
 * Returns an action object used to receive providers into the store.
 *
 * @param providers Array of providers to store.
 * @return Action object.
 */
export function receiveProviders( providers: ProviderMetadata[] ) {
	return {
		type: RECEIVE_PROVIDERS,
		providers,
	};
}

/**
 * Returns an action object used to receive models for a specific provider into the store.
 *
 * @param providerId Provider ID.
 * @param models     Array of models to store for the provider.
 * @return Action object.
 */
export function receiveProviderModels(
	providerId: string,
	models: ModelMetadata[]
) {
	return {
		type: RECEIVE_PROVIDER_MODELS,
		providerId,
		models,
	};
}
