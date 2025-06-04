// A simple test component to verify Tailwind CSS is working

document.addEventListener('DOMContentLoaded', function() {
    // Create a test component with Tailwind classes
    const createTestComponent = () => {
        // Only create if there's a container to inject into
        const container = document.querySelector('#chatbot-container');
        if (!container) return;
        
        // Create a test button with Tailwind classes
        const testButton = document.createElement('button');
        testButton.className = 'mt-4 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors';
        testButton.textContent = 'Tailwind Test Button';
        testButton.onclick = () => alert('Tailwind CSS is working!');
        
        // Create a test div with more Tailwind classes
        const testDiv = document.createElement('div');
        testDiv.className = 'mt-4 p-4 bg-gray-100 rounded-lg shadow-md';
        testDiv.innerHTML = `
            <h3 class="text-xl font-bold text-gray-800">Tailwind CSS Test</h3>
            <p class="mt-2 text-gray-600">If you can see this styled box, Tailwind CSS is working correctly!</p>
            <div class="mt-3 flex space-x-2">
                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-md">Badge 1</span>
                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-md">Badge 2</span>
                <span class="px-2 py-1 bg-red-100 text-red-800 rounded-md">Badge 3</span>
            </div>
        `;
        
        // Create a container for the test elements
        const testContainer = document.createElement('div');
        testContainer.className = 'border-t mt-6 pt-6 border-gray-200';
        testContainer.appendChild(testDiv);
        testContainer.appendChild(testButton);
        
        // Add to container
        container.appendChild(testContainer);
    };
    
    // Run the function
    createTestComponent();
});
