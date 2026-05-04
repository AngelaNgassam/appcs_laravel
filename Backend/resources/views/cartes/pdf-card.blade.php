<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            size: 85.6mm 53.98mm;
            margin: 0;
            padding: 0;
        }
        
        body {
            margin: 0;
            padding: 0;
            width: 85.6mm;
            height: 53.98mm;
            font-family: Arial, sans-serif;
            font-size: 6pt;
        }
        
        .card {
            width: 85.6mm;
            height: 53.98mm;
            border: 2px solid #007a3d;
            display: flex;
            flex-direction: column;
            margin: 0;
            padding: 0;
        }
        
        .header {
            background: linear-gradient(to right, #007a3d 0%, #007a3d 33%, #ce1126 33%, #ce1126 66%, #fcd116 66%, #fcd116 100%);
            height: 7.5mm;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 0;
        }
        
        .header-box {
            background: white;
            padding: 0.5mm 1.5mm;
            border-radius: 0.8mm;
            text-align: center;
            margin: 0;
        }
        
        .star { color: #fcd116; font-size: 6pt; font-weight: bold; margin: 0; }
        .country { font-size: 4pt; font-weight: bold; color: #007a3d; text-transform: uppercase; margin: 0; line-height: 1; }
        .motto { font-size: 2.5pt; color: #ce1126; font-style: italic; margin: 0; }
        
        .content {
            flex: 1;
            display: flex;
            padding: 1mm;
            gap: 1mm;
            position: relative;
            margin: 0;
        }
        
        .photo-box {
            width: 13mm;
            height: 17mm;
            border: 1px solid #007a3d;
            border-radius: 0.8mm;
            overflow: hidden;
            flex-shrink: 0;
            margin: 0;
            padding: 0;
        }
        
        .photo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            margin: 0;
            padding: 0;
        }
        
        .info-box {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            font-size: 4.5pt;
            margin: 0;
            padding: 0;
        }
        
        .school-name {
            text-align: center;
            font-size: 5pt;
            font-weight: bold;
            color: #ce1126;
            text-transform: uppercase;
            margin: 0 0 0.3mm 0;
            padding: 0;
            line-height: 1;
        }
        
        .info-row {
            display: flex;
            gap: 0.5mm;
            margin: 0;
            padding: 0;
            line-height: 1;
        }
        
        .info-label {
            font-weight: bold;
            color: #007a3d;
            min-width: 8mm;
            flex-shrink: 0;
            margin: 0;
            padding: 0;
        }
        
        .info-value {
            color: #1f2937;
            flex: 1;
            margin: 0;
            padding: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .badge {
            background: #fcd116;
            padding: 0.1mm 0.4mm;
            border-radius: 0.2mm;
            font-weight: bold;
            color: #000;
            font-size: 4.5pt;
            display: inline-block;
            margin: 0;
        }
        
        .class-badge {
            background: #007a3d;
            color: white;
            padding: 0.1mm 0.4mm;
            border-radius: 0.2mm;
            font-weight: bold;
            font-size: 4.5pt;
            display: inline-block;
            margin: 0;
        }
        
        .qr-box {
            position: absolute;
            bottom: 4.5mm;
            right: 1mm;
            width: 9mm;
            height: 9mm;
            background: white;
            border: 0.8px solid #007a3d;
            border-radius: 0.4mm;
            padding: 0.2mm;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        
        .qr-box svg {
            width: 100% !important;
            height: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        .footer {
            background: #007a3d;
            color: white;
            padding: 0.6mm 1mm;
            display: flex;
            justify-content: space-between;
            font-size: 3pt;
            margin: 0;
            line-height: 1;
        }
        
        .footer-item {
            margin: 0;
            padding: 0;
        }
        
        .footer-center {
            text-align: center;
            flex: 1;
            margin: 0;
            padding: 0;
        }
        
        .footer-right {
            text-align: right;
            margin: 0;
            padding: 0;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <div class="header-box">
                <div class="star">★</div>
                <div class="country">République du Cameroun</div>
                <div class="motto">Paix - Travail - Patrie</div>
            </div>
        </div>
        
        <div class="content">
            <div class="photo-box">
                @if($photoBase64)
                <img src="{{ $photoBase64 }}" alt="Photo">
                @endif
            </div>
            
            <div class="info-box">
                <div class="school-name">{{ $etablissement->nom }}</div>
                
                <div class="info-row">
                    <div class="info-label">Matricule :</div>
                    <div class="info-value"><span class="badge">{{ $eleve->matricule }}</span></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Nom :</div>
                    <div class="info-value">{{ strtoupper($eleve->nom) }}</div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Prénom :</div>
                    <div class="info-value">{{ ucwords(strtolower($eleve->prenom)) }}</div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Né(e) :</div>
                    <div class="info-value">{{ $eleve->date_naissance ? $eleve->date_naissance->format('d/m/Y') : 'N/A' }}</div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Classe :</div>
                    <div class="info-value"><span class="class-badge">{{ $classe->nom }}</span></div>
                </div>
            </div>
            
            <div class="qr-box">
                {!! $qrCode !!}
            </div>
        </div>
        
        <div class="footer">
            <div class="footer-item">Délivré: {{ now()->format('d/m/Y') }}</div>
            <div class="footer-center">CARTE OFFICIELLE</div>
            <div class="footer-item footer-right">Expire: {{ now()->addYears(2)->format('d/m/Y') }}</div>
        </div>
    </div>
</body>
</html>
