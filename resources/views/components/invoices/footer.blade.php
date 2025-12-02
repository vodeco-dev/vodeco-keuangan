@props(['companyAddress', 'companyPhone', 'companyEmail', 'companyWebsite'])

<div class="footer-section">
    <div class="footer-content">
        <div class="footer-column">
            <div class="footer-column-title">Alamat/Telepon</div>
            <p>{{ $companyAddress }}</p>
            <p>{{ $companyPhone }}</p>
        </div>
        <div class="footer-column">
            <div class="footer-column-title">Email/Situs Web</div>
            <p>{{ $companyEmail }}</p>
            <p>{{ $companyWebsite }}</p>
        </div>
    </div>
</div>

