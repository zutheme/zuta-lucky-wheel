jQuery(document).ready(function($) {
    function toggleProbabilityInputs() {
        console.log("Toggling probability inputs");
        // Get the selected value from the Game Mode dropdown (row 0)
        var mode = $('select[data-key="game_mode"]').val();
        
        // Find all inputs that manage probability
        var probInputs = $('input[data-key="probability"]');
        
        if (mode === 'weighted') {
            probInputs.closest('li').show(); // Show if weighted
        } else {
            probInputs.closest('li').hide(); // Hide if random
        }
    }

    // Run on page load
    toggleProbabilityInputs();

    // Run when dropdown changes
    $('select[data-key="game_mode"]').on('change', function() {
        toggleProbabilityInputs();
    });
});
