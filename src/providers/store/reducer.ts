import {
	RECEIVE_PROVIDERS,
	RECEIVE_PROVIDER_MODELS,
	receiveProviders,
	receiveProviderModels,
} from './actions';
import type { ProvidersModelsState } from './types';

const DEFAULT_STATE: ProvidersModelsState = {
	providers: [],
	modelsByProvider: {},
	providerLookupMap: {},
	providerModelsLookupMap: {},
};

/**
 * Reducer managing the AI providers and models.
 *
 * @since n.e.x.t
 *
 * @param state  Current state.
 * @param action Action to handle.
 * @return New state.
 */
export default function reducer(
	state: ProvidersModelsState = DEFAULT_STATE,
	action: ReturnType< typeof receiveProviders | typeof receiveProviderModels >
): ProvidersModelsState {
	switch ( action.type ) {
		case RECEIVE_PROVIDERS: {
			const { providers } = action;

			const providerLookupMap: Record< string, number > = {};
			providers.forEach( ( provider, index ) => {
				providerLookupMap[ provider.id ] = index;
			} );

			return {
				...state,
				providers,
				providerLookupMap,
			};
		}

		case RECEIVE_PROVIDER_MODELS: {
			const { providerId, models } = action;

			const providerModelsLookupMap: Record< string, number > = {};
			models.forEach( ( model, index ) => {
				providerModelsLookupMap[ model.id ] = index;
			} );

			return {
				...state,
				modelsByProvider: {
					...state.modelsByProvider,
					[ providerId ]: models,
				},
				providerModelsLookupMap: {
					...state.providerModelsLookupMap,
					[ providerId ]: providerModelsLookupMap,
				},
			};
		}

		default:
			return state;
	}
}
