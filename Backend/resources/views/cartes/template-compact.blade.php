<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Carte Scolaire - {{ $eleve->matricule }}</title>
    <style>
        @page { 
            margin: 0; 
            size: 85.6mm 53.98mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 7pt;
            line-height: 1.3;
            width: 85.6mm;
            height: 53.98mm;
        }
        
        .card-container {
            width: 100%;
            height: 100%;
            position: relative;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 2px solid #10B981;
            border-radius: 3mm;
            overflow: hidden;
        }
        
        /* En-tête moderne */
        .card-header {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            padding: 2mm 3mm;
            display: table;
            width: 100%;
            border-bottom: 2px solid #047857;
        }
        
        .header-left {
            display: table-cell;
            vertical-align: middle;
            width: 70%;
        }
        
        .header-content {
            display: table;
            width: 100%;
        }
        
        .logo-mini {
            display: table-cell;
            width: 12mm;
            vertical-align: middle;
        }
        
        .logo-mini-img {
            width: 10mm;
            height: 10mm;
            background: white;
            border-radius: 2mm;
            padding: 1mm;
            display: block;
        }
        
        .header-text {
            display: table-cell;
            vertical-align: middle;
            padding-left: 2mm;
            color: white;
        }
        
        .school-name {
            font-size: 8pt;
            font-weight: bold;
            line-height: 1.2;
            text-transform: uppercase;
        }
        
        .card-subtitle {
            font-size: 5pt;
            opacity: 0.9;
            margin-top: 0.5mm;
        }
        
        .header-right {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            width: 30%;
        }
        
        .year-badge {
            background: white;
            color: #10B981;
            padding: 1mm 2mm;
            border-radius: 2mm;
            font-size: 7pt;
            font-weight: bold;
            display: inline-block;
        }
        
        /* Corps de la carte */
        .card-body {
            padding: 3mm;
            display: table;
            width: 100%;
            height: calc(100% - 14mm - 8mm);
        }
        
        /* Section photo */
        .photo-section {
            display: table-cell;
            width: 25mm;
            vertical-align: top;
        }
        
        .photo-frame {
            width: 22mm;
            height: 28mm;
            border: 2px solid #10B981;
            border-radius: 2mm;
            overflow: hidden;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        /* Section informations */
        .info-section {
            display: table-cell;
            vertical-align: top;
            padding: 0 2mm;
        }
        
        .info-row {
            margin-bottom: 1.5mm;
            display: table;
            width: 100%;
        }
        
        .info-label {
            font-weight: 600;
            color: #047857;
            font-size: 6pt;
            display: table-cell;
            width: 18mm;
        }
        
        .info-value {
            color: #1f2937;
            font-size: 7pt;
            font-weight: 500;
            display: table-cell;
        }
        
        .matricule-box {
            background: #FCD116;
            padding: 1mm 2mm;
            border-radius: 2mm;
            font-weight: bold;
            color: #000;
            display: inline-block;
            border: 1px solid #10B981;
            font-size: 7pt;
        }
        
        .classe-badge {
            background: #10B981;
            color: white;
            padding: 1mm 2mm;
            border-radius: 2mm;
            font-weight: bold;
            display: inline-block;
            font-size: 7pt;
        }
        
        /* Section QR Code */
        .qr-section {
            display: table-cell;
            width: 20mm;
            vertical-align: top;
            text-align: center;
        }
        
        .qr-frame {
            width: 18mm;
            height: 18mm;
            background: white;
            padding: 1mm;
            border: 2px solid #10B981;
            border-radius: 2mm;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin: 0 auto;
        }
        
        .qr-code {
            width: 100%;
            height: 100%;
        }
        
        .qr-code svg {
            width: 100% !important;
            height: 100% !important;
        }
        
        .qr-label {
            font-size: 5pt;
            color: #6b7280;
            margin-top: 1mm;
            font-weight: 600;
        }
        
        /* Pied de page */
        .card-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            padding: 1.5mm 3mm;
            display: table;
            width: 100%;
            border-top: 2px solid #047857;
        }
        
        .footer-left {
            display: table-cell;
            color: white;
            font-size: 5pt;
            width: 30%;
        }
        
        .footer-center {
            display: table-cell;
            color: white;
            font-size: 6pt;
            font-weight: bold;
            text-align: center;
            width: 40%;
        }
        
        .footer-right {
            display: table-cell;
            color: white;
            font-size: 5pt;
            text-align: right;
            width: 30%;
        }
    </style>
</head>
<body>
    <div class="card-container">
        <!-- En-tête -->
        <div class="card-header">
            <div class="header-left">
                <div class="header-content">
                    @if($logoBase64)
                    <div class="logo-mini">
                        <img src="{{ $logoBase64 }}" alt="Logo" class="logo-mini-img">
                    </div>
                    @endif
                    <div class="header-text">
                        <div class="school-name">{{ $etablissement->nom }}</div>
                        <div class="card-subtitle">Carte d'Identité Scolaire</div>
                    </div>
                </div>
            </div>
            <div class="header-right">
                <div class="year-badge">{{ $anneeAcademique }}</div>
            </div>
        </div>
        
        <!-- Corps -->
        <div class="card-body">
            <!-- Photo -->
            <div class="photo-section">
                <div class="photo-frame">
                    @if($photoBase64)
                        <img src="{{ $photoBase64 }}" alt="Photo élève" class="photo">
                    @endif
                </div>
            </div>
            
            <!-- Informations -->
            <div class="info-section">
                <div class="info-row">
                    <div class="info-label">Matricule :</div>
                    <div class="info-value">
                        <span class="matricule-box">{{ $eleve->matricule }}</span>
                    </div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Nom :</div>
                    <div class="info-value">{{ strtoupper($eleve->nom) }}</div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Prénom(s) :</div>
                    <div class="info-value">{{ ucwords(strtolower($eleve->prenom)) }}</div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Né(e) le :</div>
                    <div class="info-value">{{ $eleve->date_naissance ? $eleve->date_naissance->format('d/m/Y') : 'N/A' }}</div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Lieu :</div>
                    <div class="info-value">{{ $eleve->lieu_naissance ?? 'N/A' }}</div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Sexe :</div>
                    <div class="info-value">{{ $eleve->sexe === 'M' ? 'Masculin' : 'Féminin' }}</div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Classe :</div>
                    <div class="info-value">
                        <span class="classe-badge">{{ $classe->nom }}</span>
                    </div>
                </div>
                
                @if($eleve->email)
                <div class="info-row">
                    <div class="info-label">Email :</div>
                    <div class="info-value" style="font-size: 6pt;">{{ $eleve->email }}</div>
                </div>
                @endif
            </div>
            
            <!-- QR Code -->
            <div class="qr-section">
                <div class="qr-frame">
                    <div class="qr-code">{!! $qrCode !!}</div>
                </div>
                <div class="qr-label">Scan QR</div>
            </div>
        </div>
        
        <!-- Pied de page -->
        <div class="card-footer">
            <div class="footer-left">
                Délivré le: {{ now()->format('d/m/Y') }}
            </div>
            <div class="footer-center">
                {{ $eleve->email ?? $etablissement->email ?? 'contact@etablissement.cm' }}
            </div>
            <div class="footer-right">
                Expire le: {{ now()->addYears(2)->format('d/m/Y') }}
            </div>
        </div>
    </div>
</body>
</html>
