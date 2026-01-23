// Form validation for predictions
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('prediction-form');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            const driverInputs = form.querySelectorAll('input[name*="driver_predictions"][name*="position"]');
            const constructorInputs = form.querySelectorAll('input[name*="constructor_predictions"][name*="position"]');
            
            // Check all driver positions are filled
            const driverPositions = Array.from(driverInputs).map(input => parseInt(input.value)).filter(v => !isNaN(v));
            const missingDriverPositions = driverInputs.length - driverPositions.length;
            
            if (missingDriverPositions > 0) {
                e.preventDefault();
                alert('Please predict positions for all drivers (full grid required).');
                return false;
            }
            
            // Validate driver positions are unique
            const driverDuplicates = driverPositions.filter((pos, idx) => driverPositions.indexOf(pos) !== idx);
            
            if (driverDuplicates.length > 0) {
                e.preventDefault();
                alert('Driver positions must be unique. Each driver must have a different finishing position.');
                return false;
            }
            
            // Validate constructor positions are unique
            const constructorPositions = Array.from(constructorInputs).map(input => parseInt(input.value)).filter(v => !isNaN(v));
            const constructorDuplicates = constructorPositions.filter((pos, idx) => constructorPositions.indexOf(pos) !== idx);
            
            if (constructorDuplicates.length > 0) {
                e.preventDefault();
                alert('Constructor positions must be unique. Please check your predictions.');
                return false;
            }
        });
    }
});

