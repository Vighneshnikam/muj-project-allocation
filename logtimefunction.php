<!-- JavaScript for Lockout Timer -->
<script>
    // Get the lock_until timestamp passed from PHP
    var lockUntil = <?php echo !empty($lock_until_timestamp) ? $lock_until_timestamp : 'null'; ?>;
    
    if (lockUntil) {
        // Start the countdown
        var countdownInterval = setInterval(function() {
            var currentTime = Math.floor(Date.now() / 1000); // Current time in seconds
            var remainingTime = lockUntil - currentTime; // Time left in seconds

            if (remainingTime > 0) {
                var minutes = Math.floor(remainingTime / 60);
                var seconds = remainingTime % 60;
                document.getElementById('lockout-timer').textContent = 'You are locked out. Try again in ' + minutes + ' minutes and ' + seconds + ' seconds.';
            } else {
                // Clear the timer once the lockout period is over
                clearInterval(countdownInterval);
                document.getElementById('lockout-timer').textContent = '';
            }
        }, 1000); // Update the timer every second
    }
</script>