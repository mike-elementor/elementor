import * as React from 'react';
import { ArrowDownSmallIcon, ArrowLeftIcon, ArrowRightIcon, ArrowUpSmallIcon } from '@elementor/icons';
import { Grid, ToggleButton, ToggleButtonGroup, Tooltip, Typography } from '@elementor/ui';
import { __ } from '@wordpress/i18n';

import { type FieldProps } from '../../types';

export function Direction( { value, onChange }: FieldProps ) {
	const directionsVertical = [
		{ key: 'top', label: __( 'From bottom', 'elementor' ), icon: <ArrowUpSmallIcon fontSize="tiny" /> },
		{ key: 'bottom', label: __( 'From top', 'elementor' ), icon: <ArrowDownSmallIcon fontSize="tiny" /> },
	];

	const directionsHorizontal = [
		{ key: 'left', label: __( 'From right', 'elementor' ), icon: <ArrowLeftIcon fontSize="tiny" /> },
		{ key: 'right', label: __( 'From left', 'elementor' ), icon: <ArrowRightIcon fontSize="tiny" /> },
	];

	const [ directionVertical, setDirectionVertical ] = React.useState( () => {
		if ( value.includes( 'top' ) ) {
			return 'top';
		}

		if ( value.includes( 'bottom' ) ) {
			return 'bottom';
		}

		return '';
	} );

	const [ directionHorizontal, setDirectionHorizontal ] = React.useState( () => {
		if ( value.includes( 'left' ) ) {
			return 'left';
		}

		if ( value.includes( 'right' ) ) {
			return 'right';
		}

		return '';
	} );

	React.useEffect( () => {
		onChange( `${ directionVertical }${ directionHorizontal }` );
	}, [ directionHorizontal, directionVertical, onChange ] );

	return (
		<>
			<Grid item xs={ 12 } md={ 6 }>
				<Typography variant="caption" color="text.secondary">
					{ __( 'Direction', 'elementor' ) }
				</Typography>
			</Grid>
			<Grid item xs={ 12 } md={ 6 } sx={ {
				display: 'flex',
				justifyContent: 'flex-end',
				overflow: 'hidden',
				gap: 1
			} }>
				<ToggleButtonGroup
					size="tiny"
					exclusive
					onChange={ ( event: React.MouseEvent< HTMLElement >, newValue: string ) => setDirectionVertical( newValue ) }
					value={ directionVertical }
				>
					{ directionsVertical.map( ( direction ) => {
						return (
							<Tooltip key={ direction.key } title={ direction.label } placement="top">
								<ToggleButton key={ direction.key } value={ direction.key }>
									{ direction.icon }
								</ToggleButton>
							</Tooltip>
						);
					} ) }
				</ToggleButtonGroup>

				<ToggleButtonGroup
					size="tiny"
					exclusive
					onChange={ ( event: React.MouseEvent< HTMLElement >, newValue: string ) => setDirectionHorizontal( newValue ) }
					value={ directionHorizontal }
				>
					{ directionsHorizontal.map( ( direction ) => {
						return (
							<Tooltip key={ direction.key } title={ direction.label } placement="top">
								<ToggleButton key={ direction.key } value={ direction.key }>
									{ direction.icon }
								</ToggleButton>
							</Tooltip>
						);
					} ) }
				</ToggleButtonGroup>
			</Grid>
		</>
	);
}
