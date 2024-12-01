document.querySelectorAll('.card-img-top').forEach(image => {
    image.addEventListener('click', () => {
        const cardBody = image.closest('.card').querySelector('.card-body');
        const title = cardBody.querySelector('.card-title').textContent;
        const description = cardBody.querySelector('.card-description').textContent;

        // Set the modal content
        document.getElementById('modalTitle').textContent = title;
        document.getElementById('modalDescription').textContent = description;

        // Show the modal
        document.getElementById('descriptionModal').style.display = 'flex';
    });
});

// Close the modal when the close button is clicked
document.querySelector('.close-btn').addEventListener('click', () => {
    document.getElementById('descriptionModal').style.display = 'none';
});

// Close the modal when clicking anywhere outside of the modal content
window.addEventListener('click', (event) => {
    if (event.target === document.getElementById('descriptionModal')) {
        document.getElementById('descriptionModal').style.display = 'none';
    }
});
