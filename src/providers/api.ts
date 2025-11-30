import { resolveSelect } from '@wordpress/data';
import { ProviderMetadata, ModelMetadata } from '../types';

/**
 * Internal dependencies
 */
import { store } from './store';

/**
 * Gets all registered AI providers.
 *
 * @return Promise resolving to array of providers.
 */
export async function getProviders(): Promise< ProviderMetadata[] > {
	return await resolveSelect( store ).getProviders();
}

/**
 * Gets a specific provider by its ID.
 *
 * @param id Provider ID.
 * @return Promise resolving to provider object, or undefined if not found.
 */
export async function getProvider(
	id: string
): Promise< ProviderMetadata | undefined > {
	return await resolveSelect( store ).getProvider( id );
}

/**
 * Gets all models for a specific provider.
 *
 * @param providerId Provider ID.
 * @return Promise resolving to array of models for the provider.
 */
export async function getProviderModels(
	providerId: string
): Promise< ModelMetadata[] > {
	return await resolveSelect( store ).getProviderModels( providerId );
}

/**
 * Gets a specific model by its ID for a provider.
 *
 * @param providerId Provider ID.
 * @param modelId    Model ID.
 * @return Promise resolving to model object, or undefined if not found.
 */
export async function getProviderModel(
	providerId: string,
	modelId: string
): Promise< ModelMetadata | undefined > {
	const models = await resolveSelect( store ).getProviderModels( providerId );
	return models.find( ( model ) => model.id === modelId );
}
