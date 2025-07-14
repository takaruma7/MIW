document.addEventListener('DOMContentLoaded', function() {
    // Handle add hotel button
    document.getElementById('addHotel')?.addEventListener('click', function() {
        addHotelField('hotelsContainer');
    });
    
    // Handle edit hotel button
    document.getElementById('addEditHotel')?.addEventListener('click', function() {
        addHotelField('editHotelsContainer');
    });
    
    // Auto-set currency based on package type
    document.getElementById('add_jenis_paket')?.addEventListener('change', function() {
        document.getElementById('add_currency').value = this.value === 'Haji' ? 'USD' : 'IDR';
    });
    
    document.getElementById('edit_jenis_paket')?.addEventListener('change', function() {
        document.getElementById('edit_currency').value = this.value === 'Haji' ? 'USD' : 'IDR';
    });
    
    // Initialize currency values on load
    if (document.getElementById('add_jenis_paket')) {
        document.getElementById('add_currency').value = 
            document.getElementById('add_jenis_paket').value === 'Haji' ? 'USD' : 'IDR';
    }
});

function addHotelField(containerId) {
    const container = document.getElementById(containerId);
    const index = container.children.length;
    
    const hotelDiv = document.createElement('div');
    hotelDiv.className = 'mb-3 hotel-entry';
    hotelDiv.innerHTML = `
        <div class="row">
            <div class="col-md-12">
                <label class="form-label">Hotel Name</label>
                <input type="text" class="form-control" name="additional_hotels[${index}][name]">
                
                <div class="row mt-2">
                    <div class="col-md-4">
                        <label class="form-label">Quad Room Numbers</label>
                        <input type="text" class="form-control" name="additional_hotels[${index}][quad_rooms]" placeholder="e.g., 101,102">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Triple Room Numbers</label>
                        <input type="text" class="form-control" name="additional_hotels[${index}][triple_rooms]" placeholder="e.g., 201,202">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Double Room Numbers</label>
                        <input type="text" class="form-control" name="additional_hotels[${index}][double_rooms]" placeholder="e.g., 301,302">
                        <button type="button" class="btn btn-sm btn-danger mt-2 remove-hotel">Remove Hotel</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    container.appendChild(hotelDiv);
    
    // Add event listener for remove button
    hotelDiv.querySelector('.remove-hotel').addEventListener('click', function() {
        hotelDiv.remove();
    });
}

function loadEditData(pakId) {
    fetch('get_package.php?id=' + pakId)
        .then(response => response.json())
        .then(data => {
            // Fill form fields
            document.getElementById('edit_package_id').value = data.pak_id;
            document.getElementById('edit_jenis_paket').value = data.jenis_paket;
            document.getElementById('edit_currency').value = data.currency;
            document.getElementById('edit_program_pilihan').value = data.program_pilihan;
            document.getElementById('edit_tanggal_keberangkatan').value = data.tanggal_keberangkatan;
            document.getElementById('edit_base_price_quad').value = data.base_price_quad;
            document.getElementById('edit_base_price_triple').value = data.base_price_triple;
            document.getElementById('edit_base_price_double').value = data.base_price_double;
            document.getElementById('edit_hotel_medinah').value = data.hotel_medinah || '';
            document.getElementById('edit_hotel_makkah').value = data.hotel_makkah || '';
            document.getElementById('edit_hcn').value = data.hcn || '';
            
            // Process room numbers
            const medinahRooms = JSON.parse(data.hotel_medinah_rooms || '{}');
            const makkahRooms = JSON.parse(data.hotel_makkah_rooms || '{}');
            
            document.getElementById('edit_medinah_quad_rooms').value = medinahRooms.quad?.join(',') || '';
            document.getElementById('edit_medinah_triple_rooms').value = medinahRooms.triple?.join(',') || '';
            document.getElementById('edit_medinah_double_rooms').value = medinahRooms.double?.join(',') || '';
            document.getElementById('edit_makkah_quad_rooms').value = makkahRooms.quad?.join(',') || '';
            document.getElementById('edit_makkah_triple_rooms').value = makkahRooms.triple?.join(',') || '';
            document.getElementById('edit_makkah_double_rooms').value = makkahRooms.double?.join(',') || '';
            
            // Process additional hotels with room types
            const editHotelsContainer = document.getElementById('editHotelsContainer');
            editHotelsContainer.innerHTML = '';
            
            const additionalHotels = JSON.parse(data.additional_hotels || '[]');
            const additionalHotelsRooms = JSON.parse(data.additional_hotels_rooms || '[]');
            
            additionalHotels.forEach((hotel, index) => {
                const hotelDiv = document.createElement('div');
                hotelDiv.className = 'mb-3 hotel-entry';
                hotelDiv.innerHTML = `
                    <div class="row">
                        <div class="col-md-12">
                            <label class="form-label">Hotel Name</label>
                            <input type="text" class="form-control" name="additional_hotels[${index}][name]" value="${hotel || ''}">
                            
                            <div class="row mt-2">
                                <div class="col-md-4">
                                    <label class="form-label">Quad Room Numbers</label>
                                    <input type="text" class="form-control" name="additional_hotels[${index}][quad_rooms]" 
                                        value="${additionalHotelsRooms[index]?.quad?.join(',') || ''}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Triple Room Numbers</label>
                                    <input type="text" class="form-control" name="additional_hotels[${index}][triple_rooms]" 
                                        value="${additionalHotelsRooms[index]?.triple?.join(',') || ''}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Double Room Numbers</label>
                                    <input type="text" class="form-control" name="additional_hotels[${index}][double_rooms]" 
                                        value="${additionalHotelsRooms[index]?.double?.join(',') || ''}">
                                    <button type="button" class="btn btn-sm btn-danger mt-2 remove-hotel">Remove Hotel</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                editHotelsContainer.appendChild(hotelDiv);
                
                // Add event listener for remove button
                hotelDiv.querySelector('.remove-hotel').addEventListener('click', function() {
                    hotelDiv.remove();
                });
            });
            
            // Show modal
            const editModal = new bootstrap.Modal(document.getElementById('editPackageModal'));
            editModal.show();
        })
        .catch(error => {
            console.error('Error loading package data:', error);
            alert('Error loading package data');
        });
}