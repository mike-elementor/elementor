import * as React from 'react';
import { MenuListItem } from '@elementor/editor-ui';
import { EyeIcon, PlusIcon, XIcon } from '@elementor/icons';
import {
	bindPopover,
	bindTrigger,
	Divider,
	Grid,
	IconButton,
	Popover,
	Select,
	type SelectChangeEvent,
	Stack,
	ToggleButton,
	ToggleButtonGroup,
	Typography,
	UnstableTag,
	usePopupState,
} from '@elementor/ui';
import { __ } from '@wordpress/i18n';

const DELIMITER = '/';

type PredefinedInteractionsListProps = {
	onSelectInteraction: ( interaction: string ) => void;
	selectedInteraction: string;
	onDelete?: () => void;
};

export const PredefinedInteractionsList = ( {
	onSelectInteraction,
	selectedInteraction,
	onDelete,
}: PredefinedInteractionsListProps ) => {
	return (
		<Stack sx={ { m: 1, p: 1.5 } } gap={ 2 }>
			<Header label={ __( 'Interactions', 'elementor' ) } />
			<InteractionsList
				onDelete={ () => onDelete?.() }
				selectedInteraction={ selectedInteraction }
				onSelectInteraction={ onSelectInteraction }
			/>
		</Stack>
	);
};

function Header( { label }: { label: string } ) {
	return (
		<Stack
			direction="row"
			alignItems="center"
			justifyContent="space-between"
			gap={ 1 }
			sx={ { marginInlineEnd: -0.75, py: 0.25 } }
		>
			<Typography component="label" variant="caption" color="text.secondary" sx={ { lineHeight: 1 } }>
				{ label }
			</Typography>
			<IconButton size="tiny" disabled>
				<PlusIcon fontSize="tiny" />
			</IconButton>
		</Stack>
	);
}

type InteractionListProps = {
	onDelete: () => void;
	onSelectInteraction: ( interaction: string ) => void;
	selectedInteraction: string;
};

function InteractionsList( { onSelectInteraction, selectedInteraction }: InteractionListProps ) {
	const [ interactionLabel, setInteractionLabel ] = React.useState( selectedInteraction );

	const anchorEl = React.useRef< HTMLDivElement | null >( null );

	const popupId = React.useId();
	const popupState = usePopupState( {
		variant: 'popover',
		popupId: `elementor-interactions-list-${ popupId }`,
	} );

	React.useEffect( () => {
		if ( 1 > selectedInteraction?.length ) {
			popupState.setAnchorEl( anchorEl.current );
			popupState.open();
		}
	}, [ selectedInteraction ] );

	React.useEffect( () => {
		if ( interactionLabel ) {
			onSelectInteraction( interactionLabel );
		}
	}, [ interactionLabel, onSelectInteraction ] );

	return (
		<Stack gap={ 1.5 } ref={ anchorEl }>
			<UnstableTag
				{ ...bindTrigger( popupState ) }
				fullWidth
				variant="outlined"
				label={ interactionLabel }
				showActionsOnHover
				actions={
					<>
						<IconButton size="tiny" disabled>
							<EyeIcon fontSize="tiny" />
						</IconButton>
						<IconButton size="tiny">
							<XIcon fontSize="tiny" />
						</IconButton>
					</>
				}
			/>
			<Popover
				{ ...bindPopover( popupState ) }
				disableScrollLock
				anchorEl={ anchorEl.current }
				anchorOrigin={ { vertical: 'bottom', horizontal: 'left' } }
				transformOrigin={ { vertical: 'top', horizontal: 'left' } }
				PaperProps={ {
					sx: { my: 1 },
				} }
				onClose={ () => {
					popupState.close();
				} }
			>
				<InteractionDetails
					interaction={ selectedInteraction }
					onChange={ ( newValue: string ) => {
						setInteractionLabel( newValue );
					} }
				/>
			</Popover>
		</Stack>
	);
}

type InteractionDetailsProps = {
	interaction: string;
	onChange: ( interaction: string ) => void;
};

function InteractionDetails( { interaction, onChange }: InteractionDetailsProps ) {
	const [ interactionDetails, setInteractionDetails ] = React.useState( () => {
		const [ trigger, effect, type, direction, duration, delay ] = interaction.split( DELIMITER );

		return {
			trigger: trigger || 'page-load',
			effect: effect || 'fade',
			type: type || 'in',
			direction: direction || '',
			duration: duration || '300',
			delay: delay || '0',
		};
	} );

	React.useEffect( () => {
		const newValue = Object.values( interactionDetails ).join( DELIMITER );
		onChange( newValue );
	}, [ interactionDetails, onChange ] );

	const onSelectTrigger = ( trigger: string ) => {
		setInteractionDetails( ( prev ) => {
			return { ...prev, trigger };
		} );
	};

	const onSelectEffect = ( effect: string ) => {
		setInteractionDetails( ( prev ) => {
			return { ...prev, effect };
		} );
	};

	const onSelectEffectType = ( type: string ) => {
		setInteractionDetails( ( prev ) => {
			return { ...prev, type };
		} );
	};

	const onSelectDirection = ( direction: string ) => {
		setInteractionDetails( ( prev ) => {
			return { ...prev, direction };
		} );
	};

	const onSelectDuration = ( duration: string ) => {
		setInteractionDetails( ( prev ) => {
			return { ...prev, duration };
		} );
	};

	const onSelectDelay = ( delay: string ) => {
		setInteractionDetails( ( prev ) => {
			return { ...prev, delay };
		} );
	};

	return (
		<>
			<Grid container spacing={ 2 } sx={ { width: '300px', p: 1 } }>
				<Trigger value={ interactionDetails.trigger } onChange={ onSelectTrigger } />
			</Grid>
			<Divider />
			<Grid container spacing={ 2 } sx={ { width: '300px', p: 1 } }>
				<Effect value={ interactionDetails.effect } onChange={ onSelectEffect } />
				<EffectType value={ interactionDetails.type } onChange={ onSelectEffectType } />
				<Direction value={ interactionDetails.direction ?? ''} onChange={ onSelectDirection } />
				<Duration value={ interactionDetails.duration } onChange={ onSelectDuration } />
				<Delay value={ interactionDetails.delay } onChange={ onSelectDelay } />
			</Grid>
		</>
	);
}

type FieldProps = {
	value: string;
	onChange: ( value: string ) => void;
};

function Trigger( { value, onChange }: FieldProps ) {
	const availableTriggers = Object.entries( {
		'page-load': __( 'Page load', 'elementor' ),
		'scroll-in-view': __( 'Scroll into view', 'elementor' ),
		'scroll-out-of-view': __( 'Scroll out of view', 'elementor' ),
	} ).map( ( [ key, label ] ) => ( {
		key,
		label,
	} ) );

	return (
		<>
			<Grid item xs={ 12 } md={ 6 }>
				<Typography variant="caption" color="text.secondary">
					{ __( 'Trigger', 'elementor' ) }
				</Typography>
			</Grid>
			<Grid item xs={ 12 } md={ 6 }>
				<Select
					fullWidth
					displayEmpty
					size="tiny"
					onChange={ ( event: SelectChangeEvent< string > ) => onChange( event.target.value ) }
					value={ value }
				>
					{ availableTriggers.map( ( trigger ) => {
						return (
							<MenuListItem key={ trigger.key } value={ trigger.key }>
								{ trigger.label }
							</MenuListItem>
						);
					} ) }
				</Select>
			</Grid>
		</>
	);
}

function Effect( { value, onChange }: FieldProps ) {
	const availableEffects = [
		{ key: 'fade', label: __( 'Fade', 'elementor' ) },
		{ key: 'slide', label: __( 'Slide', 'elementor' ) },
		{ key: 'scale', label: __( 'Scale', 'elementor' ) },
	];

	return (
		<>
			<Grid item xs={ 12 } md={ 6 }>
				<Typography variant="caption" color="text.secondary">
					{ __( 'Effect', 'elementor' ) }
				</Typography>
			</Grid>
			<Grid item xs={ 12 } md={ 6 }>
				<Select
					fullWidth
					displayEmpty
					size="tiny"
					value={ value }
					onChange={ ( event: SelectChangeEvent< string > ) => onChange( event.target.value ) }
				>
					{ availableEffects.map( ( effect ) => {
						return (
							<MenuListItem key={ effect.key } value={ effect.key }>
								{ effect.label }
							</MenuListItem>
						);
					} ) }
				</Select>
			</Grid>
		</>
	);
}

function EffectType( { value, onChange }: FieldProps ) {
	const availableEffectTypes = [
		{ key: 'in', label: __( 'In', 'elementor' ) },
		{ key: 'out', label: __( 'Out', 'elementor' ) },
	];

	return (
		<>
			<Grid item xs={ 12 } md={ 6 }>
				<Typography variant="caption" color="text.secondary">
					{ __( 'Type', 'elementor' ) }
				</Typography>
			</Grid>
			<Grid item xs={ 12 } md={ 6 }>
				<ToggleButtonGroup
					size="tiny"
					exclusive
					onChange={ ( event: React.MouseEvent< HTMLElement >, newValue: string ) => onChange( newValue ) }
					value={ value }
				>
					{ availableEffectTypes.map( ( effectType ) => {
						return (
							<ToggleButton key={ effectType.key } value={ effectType.key }>
								{ effectType.label }
							</ToggleButton>
						);
					} ) }
				</ToggleButtonGroup>
			</Grid>
		</>
	);
}

function Direction( { value, onChange }: FieldProps ) {
	const availableDirections = [
		{ key: 'up', label: __( 'Up', 'elementor' ) },
		{ key: 'down', label: __( 'Down', 'elementor' ) },
		{ key: 'left', label: __( 'Left', 'elementor' ) },
		{ key: 'right', label: __( 'Right', 'elementor' ) },
	];

	return (
		<>
			<Grid item xs={ 12 } md={ 6 }>
				<Typography variant="caption" color="text.secondary">
					{ __( 'Direction', 'elementor' ) }
				</Typography>
			</Grid>
			<Grid item xs={ 12 } md={ 6 }>
				<ToggleButtonGroup
					size="tiny"
					exclusive
					onChange={ ( event: React.MouseEvent< HTMLElement >, newValue: string ) => onChange( newValue ) }
					value={ value }
				>
					{ availableDirections.map( ( direction ) => {
						return (
							<ToggleButton key={ direction.key } value={ direction.key }>
								{ direction.label }
							</ToggleButton>
						);
					} ) }
				</ToggleButtonGroup>
			</Grid>
		</>
	);
}

function Duration( { value, onChange }: FieldProps ) {
	const availableDurations = [
		{ key: '100', label: __( '100 MS', 'elementor' ) },
		{ key: '200', label: __( '200 MS', 'elementor' ) },
		{ key: '300', label: __( '300 MS', 'elementor' ) },
		{ key: '400', label: __( '400 MS', 'elementor' ) },
		{ key: '500', label: __( '500 MS', 'elementor' ) },
		{ key: '750', label: __( '750 MS', 'elementor' ) },
		{ key: '1000', label: __( '1000 MS', 'elementor' ) },
		{ key: '1250', label: __( '1250 MS', 'elementor' ) },
		{ key: '1500', label: __( '1500 MS', 'elementor' ) },
	];

	return (
		<>
			<Grid item xs={ 12 } md={ 6 }>
				<Typography variant="caption" color="text.secondary">
					{ __( 'Duration', 'elementor' ) }
				</Typography>
			</Grid>
			<Grid item xs={ 12 } md={ 6 }>
				<Select
					fullWidth
					displayEmpty
					size="tiny"
					value={ value }
					onChange={ ( event: SelectChangeEvent< string > ) => onChange( event.target.value ) }
				>
					{ availableDurations.map( ( duration ) => {
						return (
							<MenuListItem key={ duration.key } value={ duration.key }>
								{ duration.label }
							</MenuListItem>
						);
					} ) }
				</Select>
			</Grid>
		</>
	);
}

function Delay( { value, onChange }: FieldProps ) {
	const availableDelays = [
		{ key: '0', label: __( '0 MS', 'elementor' ) },
		{ key: '100', label: __( '100 MS', 'elementor' ) },
		{ key: '200', label: __( '200 MS', 'elementor' ) },
		{ key: '300', label: __( '300 MS', 'elementor' ) },
		{ key: '400', label: __( '400 MS', 'elementor' ) },
		{ key: '500', label: __( '500 MS', 'elementor' ) },
	];

	return (
		<>
			<Grid item xs={ 12 } md={ 6 }>
				<Typography variant="caption" color="text.secondary">
					{ __( 'Delay', 'elementor' ) }
				</Typography>
			</Grid>
			<Grid item xs={ 12 } md={ 6 }>
				<Select
					fullWidth
					displayEmpty
					size="tiny"
					value={ value }
					onChange={ ( event: SelectChangeEvent< string > ) => onChange( event.target.value ) }
				>
					{ availableDelays.map( ( delay ) => {
						return (
							<MenuListItem key={ delay.key } value={ delay.key }>
								{ delay.label }
							</MenuListItem>
						);
					} ) }
				</Select>
			</Grid>
		</>
	);
}
