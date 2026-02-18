<footer class="footer">
        <div class="footer-content">
            <p>&copy; <?php echo date('Y'); ?> Zenvia. All rights reserved.</p>
            <div class="footer-links">
                <a href="about.php">About</a>
                <a href="privacy.php">Privacy</a>
                <a href="terms.php">Terms</a>
                <a href="contact.php">Contact</a>
            </div>
        </div>
    </footer>
    
    <script src="js/main.js"></script>
    <script src="js/ajax.js"></script>
</body>
</html>

<style>
    .footer {
        background: white;
        padding: 20px;
        margin-top: 40px;
        box-shadow: 0 -1px 3px rgba(0, 0, 0, 0.1);
    }
    
    .footer-content {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .footer-content p {
        color: #666;
        font-size: 14px;
    }
    
    .footer-links {
        display: flex;
        gap: 20px;
    }
    
    .footer-links a {
        color: #666;
        text-decoration: none;
        font-size: 14px;
    }
    
    .footer-links a:hover {
        color: #667eea;
    }
    
    @media (max-width: 600px) {
        .footer-content {
            flex-direction: column;
            gap: 15px;
            text-align: center;
        }
        
        .footer-links {
            flex-wrap: wrap;
            justify-content: center;
        }
    }
</style>
