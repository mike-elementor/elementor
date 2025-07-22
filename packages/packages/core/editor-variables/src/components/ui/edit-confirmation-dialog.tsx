import * as React from 'react';
import { useEffect, useState } from 'react';
import { useSuppressedMessage } from '@elementor/editor-current-user';
import { AlertTriangleFilledIcon } from '@elementor/icons';
import {
	Button,
	Checkbox,
	Dialog,
	DialogActions,
	DialogContent,
	DialogContentText,
	DialogTitle,
	FormControlLabel,
	Typography,
} from '@elementor/ui';
import { __ } from '@wordpress/i18n';

const MESSAGE_KEY = 'e-variables-edit-confirmation-dialog';
const TITLE_ID = 'e-variables-edit-confirmation-dialog';

export const useEditConfirmationDialog = () => {
	const [ isMessageSuppressed, suppressMessage ] = useSuppressedMessage( MESSAGE_KEY );
	const [ showDialog, setShowDialog ] = useState( false );

	return {
		isMessageSuppressed,
		suppressMessage,
		showDialog,
		setShowDialog,
	};
};

export const EditConfirmationDialog = ( {
	onConfirm,
	closeDialog,
}: {
	onConfirm?: () => void;
	closeDialog?: () => void;
} ) => {
	const { suppressMessage } = useEditConfirmationDialog();
	const [ dontShowAgain, setDontShowAgain ] = useState( false );

	const handleSave = () => {
		if ( dontShowAgain ) {
			suppressMessage();
		}
		onConfirm?.();
	};

	return (
		<Dialog open onClose={ closeDialog } aria-labelledby={ TITLE_ID } maxWidth="xs">
			<DialogTitle id={ TITLE_ID } display="flex" alignItems="center" gap={ 1 }>
				<AlertTriangleFilledIcon color="secondary" />
				{ __( 'Changes to variables go live right away.', 'elementor' ) }
			</DialogTitle>
			<DialogContent>
				<DialogContentText variant="body2" color="textPrimary">
					{ __(
						"Don't worry - all other changes you make will wait until you publish your site.",
						'elementor'
					) }
				</DialogContentText>
			</DialogContent>
			<DialogActions sx={ { justifyContent: 'space-between', alignItems: 'center' } }>
				<FormControlLabel
					control={
						<Checkbox
							checked={ dontShowAgain }
							onChange={ ( event: React.ChangeEvent< HTMLInputElement > ) =>
								setDontShowAgain( event.target.checked )
							}
							size="small"
						/>
					}
					label={ <Typography variant="body2">{ __( "Don't show me again", 'elementor' ) }</Typography> }
				/>
				<div>
					<Button color="secondary" onClick={ closeDialog }>
						{ __( 'Keep editing', 'elementor' ) }
					</Button>
					<Button variant="contained" color="secondary" onClick={ handleSave } sx={ { ml: 1 } }>
						{ __( 'Save', 'elementor' ) }
					</Button>
				</div>
			</DialogActions>
		</Dialog>
	);
};
