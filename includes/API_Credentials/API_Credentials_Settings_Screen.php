<?php
/**
 * Class WordPress\AI_Client\API_Credentials\API_Credentials_Settings_Screen
 *
 * @since 0.1.0
 * @package WordPress\AI_Client
 */

namespace WordPress\AI_Client\API_Credentials;

use WordPress\AiClient\Providers\DTO\ProviderMetadata;

/**
 * Class for a settings screen which displays AI provider API credentials fields.
 *
 * @since 0.1.0
 */
class API_Credentials_Settings_Screen {

	/**
	 * The screen slug.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private string $screen_slug;

	/**
	 * The screen title.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private string $screen_title;

	/**
	 * The screen description.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private string $screen_description;

	/**
	 * The option group for the settings.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private string $option_group;

	/**
	 * The option name for storing the provider credentials.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private string $option_name;

	/**
	 * The providers metadata collected from the PHP AI Client SDK.
	 *
	 * @since 0.1.0
	 * @var array<string, ProviderMetadata> An array of provider metadata, keyed by provider ID.
	 */
	private array $providers_metadata = array();

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param string                          $screen_slug        The screen slug.
	 * @param string                          $screen_title       The screen title.
	 * @param string                          $screen_description The screen description.
	 * @param string                          $option_group       The option group for the settings.
	 * @param string                          $option_name        The option name for storing the provider credentials.
	 * @param array<string, ProviderMetadata> $providers_metadata An array of provider metadata, keyed by provider ID.
	 */
	public function __construct( string $screen_slug, string $screen_title, string $screen_description, string $option_group, string $option_name, array $providers_metadata ) {
		$this->screen_slug        = $screen_slug;
		$this->screen_title       = $screen_title;
		$this->screen_description = $screen_description;
		$this->option_group       = $option_group;
		$this->option_name        = $option_name;
		$this->providers_metadata = $providers_metadata;
	}

	/**
	 * Initializes the provider settings screen.
	 *
	 * This method adds a settings section for provider API credentials, including a field for each provider that
	 * requires API key authentication.
	 *
	 * @since 0.1.0
	 */
	public function initialize_screen(): void {
		$settings_section = 'wp-ai-client-provider-credentials';

		add_settings_section(
			$settings_section,
			'',
			function () {
				?>
				<p class="description">
					<?php echo wp_kses( $this->screen_description, $this->kses_description_allowed_html() ); ?>
				</p>
				<?php
			},
			$this->screen_slug
		);

		foreach ( $this->providers_metadata as $provider_metadata ) {
			$provider_id              = $provider_metadata->getId();
			$provider_name            = $provider_metadata->getName();
			$provider_credentials_url = $provider_metadata->getCredentialsUrl();

			$field_id   = "wp-ai-client-provider-api-key-{$provider_id}";
			$field_args = array(
				'type'      => 'password',
				'label_for' => $field_id,
				'id'        => $field_id,
				'name'      => $this->option_name . '[' . $provider_id . ']',
			);
			if ( $provider_credentials_url ) {
				$field_args['description'] = sprintf(
					/* translators: 1: provider name, 2: URL to the provider's API credentials page. */
					__( 'Create and manage your %1$s API keys in the <a href="%2$s" target="_blank" rel="noopener noreferrer">%1$s account settings<span class="screen-reader-text"> (opens in a new tab)</span></a>.', 'wp-ai-client' ),
					$provider_name,
					esc_url( $provider_credentials_url )
				);
			}

			add_settings_field(
				$field_id,
				$provider_name,
				array( $this, 'render_field' ),
				$this->screen_slug,
				$settings_section,
				$field_args
			);
		}
	}

	/**
	 * Renders the provider settings screen.
	 *
	 * @since 0.1.0
	 */
	public function render_screen(): void {
		?>
		<div class="wrap">
			<h1>
				<?php echo esc_html( $this->screen_title ); ?>
			</h1>

			<form action="options.php" method="post">
				<?php settings_fields( $this->option_group ); ?>
				<?php do_settings_sections( $this->screen_slug ); ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Renders a settings field based on the given arguments.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, string> $args Field arguments set up during `add_settings_field()`.
	 */
	public function render_field( array $args ): void {
		$type           = $args['type'] ?? 'text';
		$id             = $args['id'] ?? '';
		$name           = $args['name'] ?? '';
		$description    = $args['description'] ?? '';
		$description_id = $args['id'] . '_description';

		if ( str_contains( $name, '[' ) ) {
			$parts  = explode( '[', $name, 2 );
			$option = get_option( $parts[0] );
			$subkey = trim( $parts[1], ']' );
			if ( is_array( $option ) && isset( $option[ $subkey ] ) ) {
				if ( is_string( $option[ $subkey ] ) ) {
					$value = $option[ $subkey ];
				} elseif ( is_numeric( $option[ $subkey ] ) ) {
					$value = (string) $option[ $subkey ];
				} else {
					$value = '';
				}
			} else {
				$value = '';
			}
		} else {
			$option = get_option( $name );
			if ( is_string( $option ) ) {
				$value = $option;
			} else {
				$value = '';
			}
		}

		?>
		<input
			type="<?php echo esc_attr( $type ); ?>"
			id="<?php echo esc_attr( $id ); ?>"
			name="<?php echo esc_attr( $name ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
			<?php echo $description ? 'aria-describedby="' . esc_attr( $description_id ) . '"' : ''; ?>
		>
		<?php

		if ( $description ) {
			?>
			<p
				id="<?php echo esc_attr( $description_id ); ?>"
				class="description"
			>
				<?php echo wp_kses( $description, $this->kses_description_allowed_html() ); ?>
			</p>
			<?php
		}
	}

	/**
	 * Returns the allowed HTML tags and attributes for descriptions using wp_kses().
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, array<string, mixed>> Allowed HTML tags and their attributes.
	 */
	private function kses_description_allowed_html(): array {
		return array(
			'strong' => array(),
			'em'     => array(),
			'span'   => array(
				'class' => array(),
			),
			'a'      => array(
				'class'  => array(),
				'href'   => array(),
				'target' => array(),
				'rel'    => array(),
			),
		);
	}
}
