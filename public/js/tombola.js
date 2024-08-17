document.addEventListener('DOMContentLoaded', function () {
    const winnerContainer = document.getElementById('winner-container');
    const confettiContainer = document.getElementById('confetti-container');

    if (winnerContainer && confettiContainer) {
        // Afficher l'animation de confetti
        confettiContainer.style.display = 'block';

        // Afficher le texte du gagnant avec animation
        winnerContainer.classList.add('animate-winner');

        // Arrêter l'animation après 5 secondes
        setTimeout(function () {
            confettiContainer.style.display = 'none';
            winnerContainer.classList.remove('animate-winner');
        }, 5000);
    }
});
