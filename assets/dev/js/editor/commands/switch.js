import CommandsBase from 'elementor-api/modules/command-base';

export class Switch extends CommandsBase {
	validateArgs( args ) {
		this.requireArgument( 'id', args );
	}

	apply( args ) {
		const { id, onClose } = args;

		return $e.run( 'editor/documents/close', {
			id: elementor.documents.getCurrentId(),
			onClose,
		} )
			.then( () => {
				$e.run( 'editor/documents/open', { id } );
			} );
	}
}

export default Switch;
