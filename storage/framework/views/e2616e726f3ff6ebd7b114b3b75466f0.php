<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Planche d'impression - Cartes Scolaires</title>
    <style>
        @page { 
            margin: 10mm;
            size: A4 portrait;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'DejaVu Sans', Arial, sans-serif;
        }
        
        .planche-container {
            width: 100%;
        }
        
        .carte-wrapper {
            width: 85.6mm;
            height: 53.98mm;
            margin: 0 auto 5mm auto;
            page-break-inside: avoid;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        /* Styles de la carte */
        .card-recto {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: 2px solid #4a5568;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
        }
        
        .card-header {
            background: rgba(255, 255, 255, 0.95);
            padding: 6px 10px;
            border-bottom: 3px solid #f59e0b;
            display: table;
            width: 100%;
        }
        
        .logo-section {
            display: table-cell;
            width: 50px;
            vertical-align: middle;
        }
        
        .logo {
            max-height: 35px;
            max-width: 45px;
            display: block;
        }
        
        .header-text {
            display: table-cell;
            vertical-align: middle;
            padding-left: 8px;
        }
        
        .school-name {
            font-size: 10pt;
            font-weight: bold;
            color: #1f2937;
            text-transform: uppercase;
            line-height: 1.2;
            margin-bottom: 2px;
        }
        
        .card-title {
            font-size: 7pt;
            color: #6b7280;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .card-body {
            padding: 8px 10px;
            background: rgba(255, 255, 255, 0.98);
            height: calc(100% - 50px);
            display: table;
            width: 100%;
        }
        
        .photo-section {
            display: table-cell;
            width: 90px;
            vertical-align: top;
            padding-right: 8px;
        }
        
        .photo-frame {
            width: 85px;
            height: 110px;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            overflow: hidden;
            background: #f9fafb;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .info-section {
            display: table-cell;
            vertical-align: top;
            padding-top: 2px;
        }
        
        .info-row {
            margin-bottom: 4px;
            display: table;
            width: 100%;
        }
        
        .info-label {
            font-weight: 600;
            color: #4b5563;
            font-size: 7pt;
            display: table-cell;
            width: 55px;
            padding-right: 4px;
        }
        
        .info-value {
            color: #1f2937;
            font-size: 8pt;
            font-weight: 500;
            display: table-cell;
        }
        
        .matricule-highlight {
            background: #fef3c7;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
            color: #92400e;
            display: inline-block;
        }
        
        .qr-section {
            position: absolute;
            bottom: 8px;
            right: 10px;
            width: 50px;
            height: 50px;
            background: white;
            padding: 3px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .qr-code {
            width: 100%;
            height: 100%;
        }
        
        .qr-code svg {
            width: 100% !important;
            height: 100% !important;
        }
        
        .year-badge {
            position: absolute;
            top: 52px;
            right: 10px;
            background: #10b981;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 7pt;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .watermark {
            position: absolute;
            bottom: 3px;
            left: 10px;
            font-size: 6pt;
            color: #9ca3af;
            font-style: italic;
        }
        
        .planche-header {
            text-align: center;
            margin-bottom: 5mm;
            padding-bottom: 3mm;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .planche-title {
            font-size: 14pt;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 2mm;
        }
        
        .planche-info {
            font-size: 9pt;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="planche-container">
        <div class="planche-header">
            <div class="planche-title">📋 PLANCHE D'IMPRESSION - CARTES SCOLAIRES</div>
            <div class="planche-info">
                Généré le <?php echo e(now()->format('d/m/Y à H:i')); ?> | 
                Total: <?php echo e(count($cartes)); ?> carte(s)
            </div>
        </div>
        
        <?php $__currentLoopData = $cartes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $carte): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="carte-wrapper <?php echo e(($index + 1) % 3 === 0 && $index < count($cartes) - 1 ? 'page-break' : ''); ?>">
                <div class="card-recto">
                    <div class="card-header">
                        <div class="logo-section">
                            <?php if($carte['logoBase64']): ?>
                                <img src="<?php echo e($carte['logoBase64']); ?>" alt="Logo" class="logo">
                            <?php endif; ?>
                        </div>
                        <div class="header-text">
                            <div class="school-name"><?php echo e($carte['etablissement']->nom); ?></div>
                            <div class="card-title">CARTE D'IDENTITÉ SCOLAIRE</div>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <div class="photo-section">
                            <div class="photo-frame">
                                <?php if($carte['photoBase64']): ?>
                                    <img src="<?php echo e($carte['photoBase64']); ?>" alt="Photo élève" class="photo">
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="info-section">
                            <div class="info-row">
                                <div class="info-label">Matricule</div>
                                <div class="info-value">
                                    <span class="matricule-highlight"><?php echo e($carte['eleve']->matricule); ?></span>
                                </div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-label">Nom</div>
                                <div class="info-value"><?php echo e(strtoupper($carte['eleve']->nom)); ?></div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-label">Prénom(s)</div>
                                <div class="info-value"><?php echo e(ucwords(strtolower($carte['eleve']->prenom))); ?></div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-label">Né(e) le</div>
                                <div class="info-value"><?php echo e($carte['eleve']->date_naissance ? $carte['eleve']->date_naissance->format('d/m/Y') : 'N/A'); ?></div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-label">Lieu</div>
                                <div class="info-value"><?php echo e($carte['eleve']->lieu_naissance ?? 'N/A'); ?></div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-label">Sexe</div>
                                <div class="info-value"><?php echo e($carte['eleve']->sexe === 'M' ? 'Masculin' : 'Féminin'); ?></div>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-label">Classe</div>
                                <div class="info-value" style="font-weight: bold; color: #7c3aed;"><?php echo e($carte['classe']->nom); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="qr-section">
                        <div class="qr-code"><?php echo $carte['qrCode']; ?></div>
                    </div>
                    
                    <div class="year-badge"><?php echo e($carte['anneeAcademique']); ?></div>
                    <div class="watermark">Généré le <?php echo e(now()->format('d/m/Y')); ?></div>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</body>
</html>
<?php /**PATH D:\LICENCE\projets\apppcs_project\apppcs_project\apppcs_backend\resources\views/cartes/planche-impression.blade.php ENDPATH**/ ?>