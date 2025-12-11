import type { ProviderMetadata, ModelMetadata } from '../../types';

export type ProvidersModelsState = {
	providers: ProviderMetadata[];
	modelsByProvider: Record< string, ModelMetadata[] >;
	providerLookupMap: Record< string, number >;
	providerModelsLookupMap: Record< string, Record< string, number > >;
};
