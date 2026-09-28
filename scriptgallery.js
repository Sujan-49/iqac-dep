const departmentLinks = document.querySelectorAll('.gallery-list a');
const imageList = document.querySelector('.image-list');
const noImagesMsg = document.querySelector('.no-images-message');

// Example mock data (you can load dynamically from a database using fetch/ajax if needed)
const departmentImages = {
  "Information Technology": [
    "https://via.placeholder.com/200x120?text=IT+1",
    "https://via.placeholder.com/200x120?text=IT+2"
  ],
  "Computer Networking": [
    "https://via.placeholder.com/200x120?text=CN+1",
    "https://via.placeholder.com/200x120?text=CN+2"
  ],
  "Electronics and Communication": [
    "https://via.placeholder.com/200x120?text=ECE+1",
    "https://via.placeholder.com/200x120?text=ECE+2"
  ],
  "Mechanical Engineering": []
};

departmentLinks.forEach(link => {
  link.addEventListener('click', e => {
    e.preventDefault();
    const dept = link.dataset.department;
    const images = departmentImages[dept] || [];

    imageList.innerHTML = '';
    if (images.length === 0) {
      noImagesMsg.style.display = 'block';
    } else {
      noImagesMsg.style.display = 'none';
      images.forEach(src => {
        const img = document.createElement('img');
        img.src = src;
        img.alt = `${dept} MOU`;
        imageList.appendChild(img);
      });
    }
  });
});
