import apiFetch from '@wordpress/api-fetch';
import type { ProviderMetadata, ModelMetadata } from '../../types';
import { receiveProviders, receiveProviderModels } from './actions';

/**
 * Resolver for getProviders selector.
 *
 * @since n.e.x.t
 *
 * @return Thunk function.
 */
export function getProviders() {
	// @ts-expect-error - registry types are not yet available
	return async ( { dispatch } ) => {
		const providers: ProviderMetadata[] = await apiFetch( {
			path: '/wp-ai/v1/providers',
		} );

		dispatch( receiveProviders( providers || [] ) );
	};
}

/**
 * Resolver for getProvider selector.
 *
 * Falls through to getProviders to ensure providers are loaded.
 *
 * @since n.e.x.t
 *
 * @return Thunk function.
 */
export function getProvider() {
	// @ts-expect-error - registry types are not yet available
	return ( { select } ) => {
		select.getProviders();
	};
}

/**
 * Resolver for getProviderModels selector.
 *
 * @since n.e.x.t
 *
 * @param providerId Provider ID.
 * @return Thunk function.
 */
export function getProviderModels( providerId: string ) {
	// @ts-expect-error - registry types are not yet available
	return async ( { dispatch } ) => {
		let models: ModelMetadata[] = [];
		try {
			models = await apiFetch( {
				path: `/wp-ai/v1/providers/${ providerId }/models`,
			} );
		} catch ( error ) {
			// If the provider is not configured, ignore the error and return an empty models array.
			if (
				typeof error === 'object' &&
				error !== null &&
				'code' in error &&
				( error as any ).code === 'ai_provider_not_configured'
			) {
				models = [];
			} else {
				throw error;
			}
		}

		dispatch( receiveProviderModels( providerId, models ) );
	};
}

/**
 * Resolver for getProviderModel selector.
 *
 * Falls through to getProviderModels to ensure models are loaded.
 *
 * @since n.e.x.t
 *
 * @param providerId Provider ID.
 * @return Thunk function.
 */
export function getProviderModel( providerId: string ) {
	// @ts-expect-error - registry types are not yet available
	return ( { select } ) => {
		select.getProviderModels( providerId );
	};
}
